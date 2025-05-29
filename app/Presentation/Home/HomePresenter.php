<?php
// app/Presentation/Home/HomePresenter.php

namespace App\Presentation\Home;

use Nette;
use Nette\Application\UI\Form;
use Nette\Database\Explorer;

final class HomePresenter extends Nette\Application\UI\Presenter
{
    public function __construct(
        private Explorer $database,
    ) {}

    /**
     * renderDefault()
     * Volá se automaticky při zobrazení akce "default"
     * Načte posledních 6 příspěvků z tabulky 'posts' setříděných vzestupně podle data vytvoření
     * a předá je šabloně jako proměnnou $posts.
     */
    public function renderDefault(): void
    {
        $this->template->posts = $this->database
            ->table('posts')
            ->order('created_at ASC')
            ->limit(6);
    }

    /**
     * createComponentPostForm()
     * Vytvoří a nakonfiguruje formulář pro přidání nové poznámky.
     * Formulář obsahuje:
     *  - titulek (text)
     *  - obsah (textarea)
     *  - deadline (date)
     *  - výběr barvy (radio list)
     * Po úspěšném odeslání se volá metoda postFormSucceeded().
     * 
     * Toto je specifcké pro Nette. Tvoříme komponentu pomocí createComponent.
     * 
     */

    protected function createComponentPostForm(): Form
    {
        $form = new Form;
        $form->setMethod('POST');
        $this->configureFormRenderer($form);

        // Titulek
        $form->addText('title', 'Titulek:')
            ->setRequired('Prosím, zadej titulek.')
            ->setHtmlAttribute('class', 'form-control');

        // Obsah
        $form->addTextArea('content', 'Obsah:')
            ->setRequired('Prosím, zadej obsah.')
            ->setHtmlAttribute('class', 'form-control');

        // Deadline (výchozí hodnota zítra)
        $form->addDate('deadline', 'Deadline:')
            ->setRequired('Prosím, zadej datum.')
            ->setDefaultValue(new \DateTime('+1 day'))
            ->setHtmlAttribute('class', 'form-control');

        // Barva (radio list)
        $form->addRadioList('color', 'Barva:', [
            'cervena' => 'Červená',
            'modra'   => 'Modrá',
            'fialova' => 'Fialová',
        ])->setRequired('Vyber barvu.');

        // Tlačítko odeslat
        $form->addSubmit('send', 'Publikovat')
            ->setHtmlAttribute('class', 'btn save');

        // Při úspěchu voláme metodu postFormSucceeded()
        $form->onSuccess[] = [$this, 'postFormSucceeded'];
        return $form;
    }

    /**
     * createComponentEditForm()
     * Vytvoří a nakonfiguruje formulář pro editaci stávající poznámky.
     * Podobně jako postForm, ale navíc obsahuje skryté pole 'id'.
     * Po úspěšném odeslání se volá metoda editFormSucceeded().
     * 
     * Toto je specifcké pro Nette. Tvoříme komponentu pomocí createComponent.
     * 
     */
    protected function createComponentEditForm(): Form
    {
        $form = new Form;
        $form->setMethod('POST');
        $this->configureFormRenderer($form);

        // Skryté ID pro identifikaci záznamu
        $form->addHidden('id');

        // Ostatní pole shodně s postForm
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

        // Tlačítko pro uložení změn
        $form->addSubmit('send', 'Uložit změny')
            ->setHtmlAttribute('class', 'btn save');

        // Při úspěchu voláme metodu editFormSucceeded()
        $form->onSuccess[] = [$this, 'editFormSucceeded'];
        return $form;
    }

    /**
     * configureFormRenderer()
     * Nastaví wrappery a třídy pro jednotný vzhled všech formulářů.
     * 
     * tato API pro přizpůsobení HTML výstupu formuláře je součást Nette Forms.
     * 
     */
    private function configureFormRenderer(Form $form): void
    {
        //tato API pro přizpůsobení HTML výstupu formuláře je součást Nette Forms.
        $renderer = $form->getRenderer();

        // Odstraníme defaultní container pro všechny controls
        $renderer->wrappers['controls']['container'] = null;

        // Obal každého label+control do <div class="form-group">
        $renderer->wrappers['pair']['container'] = 'div class="form-group"';
        $renderer->wrappers['pair']['.error']    = 'has-error';

        // Label a control bez dalších wrapperů
        $renderer->wrappers['label']['container']   = null;
        $renderer->wrappers['control']['container'] = null;

        // Chybové zprávy uvnitř divu with class form-error
        $renderer->wrappers['error']['container'] = 'div class="form-error"';
    }

    /**
     * postFormSucceeded()
     * Zpracuje odeslání přidávacího formuláře.
     * Vloží nový záznam do tabulky 'posts', přidá flash message a přesměruje.
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
        // Toto je Nette API pro flash zprávy a redirecty. Je to zabudované v Presenter třídě.
        $this->flashMessage('Poznámka byla úspěšně přidána.', 'success');
        $this->redirect('this'); // znovu zobrazí stejnou stránku
    }

    /**
     * editFormSucceeded()
     * Zpracuje odeslání editačního formuláře.
     * Podle skrytého 'id' aktualizuje odpovídající řádek v tabulce.
     */
    public function editFormSucceeded(Form $form, \stdClass $values): void
    {
        $this->database->table('posts')
            ->get($values->id)
            ->update([
                'title'    => $values->title,
                'content'  => $values->content,
                'deadline' => $values->deadline->format('Y-m-d'),
                'color'    => $values->color,
            ]);
        $this->flashMessage('Poznámka byla úspěšně upravena.', 'success');
        $this->redirect('this');
    }

    /**
     * handleDelete()
     * AJAXová i ne-AJAXová signal handler pro smazání poznámky.
     * Pokud je požadavek AJAX (isAjax), redraw fragmentu 'postArchive',
     * jinak klasicky redirect.
     * 
     * signal handler je součástí Nette a slouží k obsluze AJAX požadavků.
     * 
     */
    public function handleDelete(int $id): void
    {
        $post = $this->database->table('posts')->get($id);
        if ($post) {
            $post->delete();
            $this->flashMessage('Poznámka byla úspěšně smazána.', 'success');
        } else {
            $this->flashMessage('Tento příspěvek neexistuje.', 'error');
        }
        // Kotrolujeme, zda je požadavek AJAX nebo ne. Toto je specifické pro Nette.
        if ($this->isAjax()) {
            $this->redrawControl('postArchive');
        } else {
            $this->redirect('this');
        }
    }

    /**
     * handleChangeColor()
     * Signal pro změnu barvy jednotlivé poznámky.
     * Přepíná barvu cyklicky mezi předdefinovanými.
     * 
     */
    public function handleChangeColor(int $id): void
    {
        $post = $this->database->table('posts')->get($id);
        if (!$post) {
            $this->error('Příspěvek nenalezen');
        }

        // Pole možných barev
        $colors  = ['cervena','modra','fialova'];
        $current = array_search($post->color, $colors, true);
        $next    = $colors[(($current === false ? 0 : $current) + 1) % count($colors)];

        // Uložíme novou barvu do DB
        $post->update(['color' => $next]);

        if ($this->isAjax()) {
            // V případě AJAXu pošleme zpět JSON
            $this->sendJson([
                'id'    => $id,
                'color' => $next,
            ]);
        } else {
            // Jinak klasicky refresh stránky
            $this->redirect('this');
        }
    }
}
