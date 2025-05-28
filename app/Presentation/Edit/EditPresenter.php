<?php
////app\Presentation\Edit\EditPresenter.php

namespace App\Presentation\Edit;

use Nette;
use Nette\Application\UI\Form;

final class EditPresenter extends Nette\Application\UI\Presenter
{
	public function __construct(
		private Nette\Database\Explorer $database,
	) {
	}


protected function createComponentPostForm(): Form
{
	$form = new Form;
	$form->addText('title', 'Title:')
		->setRequired();
	$form->addTextArea('content', 'Content:')
		->setRequired();

	$form->addSubmit('send', 'Save and publish');
	$form->onSuccess[] = $this->postFormSucceeded(...);

	return $form;
}


private function postFormSucceeded(array $data): void
{
	$post = $this->database
		->table('posts')
		->insert($data);

	$this->flashMessage('Post was published successfully.', 'success');
	$this->redirect('Post:show', $post->id);
}


}


