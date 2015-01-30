# Nette Plupload
Nette Plupload can be installed over [composer](https://getcomposer.org/download/).

#### Installation
```
composer require doublemcz/nette-plupload
```

Copy folder 'public' from 'src' dir to your www folder and set right path in links in HTML document. 

#### Usage

##### Add to @layout.latte
Put includes to your html. Package needs jQuery to work
```html
<link rel="stylesheet" href="{$basePath}/pathToPluploadPublicFolder/css/nette.plupload.css">
<!--- If you have jQuery in your project already, you can omit following include -->
<script src="https://code.jquery.com/jquery-2.1.3.min.js"></script>
<script src="{$basePath}/pathToPluploadPublicFolder/js/plupload.full.min.js"></script>
<!-- Based on your language choose your language code, you can see possible languages in 'public/langs' folder -->
<script src="{$basePath}/pathToPluploadPublicFolder/js/langs/cs.js"></script>
<script src="{$basePath}/pathToPluploadPublicFolder/js/jquery.nette.plupload.js"></script>
```

NettePlupload is automatically initialized by event in jquery.nette.plupload.js

##### In Presenter
```php
public function createComponentUploadForm($name)
{
	$form = new \Doublemcz\NettePlupload\Form($this, $name);
	$form->addPlupload('plupload');
	$form->addSubmit('submit', 'Send form');
	$form->onSuccess[] = [$this, 'onSuccess'];

	return $form;
}

public function onSuccess(Form $form)
{
	$files = $form->getValues()->plupload;
	foreach ($files as $file) {
	  // work with uploaded file
	}
}
```

##### In latte
```
{form uploadForm}
  {input plupload}
  {input submit}
{/form}
```
