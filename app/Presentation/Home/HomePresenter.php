<?php
// app/Presentation/Home/HomePresenter.php

namespace App\Presentation\Home;

use Nette;
use Nette\Application\UI\Form;
use Nette\Database\Explorer;
use Nette\Forms\Controls;

final class HomePresenter extends Nette\Application\UI\Presenter
{
    public function __construct(
        private Explorer $database,
    ) {}

    /**
     * Render výchozí akce: posledních 5 příspěvků
     */
    public function renderDefault(): void
    {
        $this->template->posts = $this->database
            ->table('posts')
            ->order('created_at DESC')
            ->limit(5);
    }

    /**
     * Komponenta formuláře pro přidání nové poznámky
     */
    protected function createComponentPostForm(): Form
    {
        $form = new Form;
        $form->setMethod('POST');

        $this->configureFormRenderer($form);

        $form->addText('title', 'Titulek:')
            ->setRequired('Prosím, zadej titulek.')
            ->setHtmlAttribute('class', 'form-control');

        $form->addTextArea('content', 'Obsah:')
            ->setRequired('Prosím, zadej obsah.')
            ->setHtmlAttribute('class', 'form-control');

        $form->addDate('deadline', 'Deadline:')
            ->setRequired('Prosím, zadej datum.')
            ->setDefaultValue(new \DateTime('+1 day'))
            ->setHtmlAttribute('class', 'form-control');

        $form->addRadioList('color', 'Barva:', [
            'cervena' => 'Červená',
            'modra'   => 'Modrá',
            'fialova' => 'Fialová',
        ])->setRequired('Vyber barvu.');

        $form->addSubmit('send', 'Publikovat')
            ->setHtmlAttribute('class', 'btn save');

        $form->onSuccess[] = [$this, 'postFormSucceeded'];
        return $form;
    }

    /**
     * Komponenta formuláře pro editaci poznámky
     */
    protected function createComponentEditForm(): Form
    {
        $form = new Form;
        $form->setMethod('POST');

        $this->configureFormRenderer($form);

        $form->addHidden('id');

        $form->addText('title', 'Titulek:')
            ->setRequired('Prosím, zadej titulek.')
            ->setHtmlAttribute('class', 'form-control');

        $form->addTextArea('content', 'Obsah:')
            ->setRequired('Prosím, zadej obsah.')
            ->setHtmlAttribute('class', 'form-control');

        $form->addDate('deadline', 'Deadline:')
            ->setRequired('Prosím, zadej datum.')
            ->setHtmlAttribute('class', 'form-control');

        $form->addRadioList('color', 'Barva:', [
            'cervena' => 'Červená',
            'modra'   => 'Modrá',
            'fialova' => 'Fialová',
        ])->setRequired('Vyber barvu.');

        $form->addSubmit('send', 'Uložit změny')
            ->setHtmlAttribute('class', 'btn save');

        $form->onSuccess[] = [$this, 'editFormSucceeded'];
        return $form;
    }

    /**
     * Konfigurace rendereru pro společné wrappery a třídy
     */
    private function configureFormRenderer(Form $form): void
    {
        $renderer = $form->getRenderer();

        // Odebíráme kontejner celého controls (nepotřebujeme)
        $renderer->wrappers['controls']['container'] = null;

        // Každé label+control obalíme do <div class="form-group">
        $renderer->wrappers['pair']['container']       = 'div class="form-group"';
        $renderer->wrappers['pair']['.error']          = 'has-error';

        // Label bez dalšího wrapperu
        $renderer->wrappers['label']['container']      = null;

        // Control element (input/textarea/select) uvnitř wrapperu
        $renderer->wrappers['control']['container']    = null;

        // Chybové hlášky
        $renderer->wrappers['error']['container']      = 'div class="form-error"';
    }

    /**
     * Zpracování přidání nové poznámky
     */
    public function postFormSucceeded(Form $form, \stdClass $values): void
    {
        $this->database->table('posts')->insert([
            'title'      => $values->title,
            'content'    => $values->content,
            'deadline'   => $values->deadline->format('Y-m-d'),
            'color'      => $values->color,
            'created_at' => new \DateTime,
        ]);
        $this->flashMessage('Poznámka byla úspěšně přidána.', 'success');
        $this->redirect('this');
    }

    /**
     * Zpracování úpravy poznámky
     */
    public function editFormSucceeded(Form $form, \stdClass $values): void
    {
        $this->database->table('posts')->get($values->id)->update([
            'title'    => $values->title,
            'content'  => $values->content,
            'deadline' => $values->deadline->format('Y-m-d'),
            'color'    => $values->color,
        ]);
        $this->flashMessage('Poznámka byla úspěšně upravena.', 'success');
        $this->redirect('this');
    }
}
