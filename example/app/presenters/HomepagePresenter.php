<?php

namespace App\Presenters;

use Doublemcz\NettePlupload\Form;
use Nette;

/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter
{
	public function createComponentUploadForm($name)
	{
		$form = new Form($this, $name);
		$form->addPlupload('plupload');
		$form->addSubmit('submit', 'Send form');
		$form->onSuccess[] = [$this, 'onSuccess'];

		return $form;
	}

	public function onSuccess(Form $form)
	{
		dump($form->getValues());
		exit;
	}
}
