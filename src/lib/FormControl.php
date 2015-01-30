<?php
namespace Doublemcz\NettePlupload;

use Nette;
use Nette\ComponentModel\IContainer;

class FormControl extends Nette\Application\UI\Control implements Nette\Forms\IControl
{
	/**
	 * @var array
	 */
	protected $value = array();

	/**
	 * Absolute path of directory where the files will be stored
	 *
	 * @var string
	 */
	protected $uploadDir = '';

	/**
	 * Options for JS Plupload instance
	 *
	 * @var array
	 */
	protected $uploaderOptions = array();

	/**
	 * Required by Nette
	 * @var array user options
	 */
	private $options = array();

	/**
	 * @var Form
	 */
	protected $form;

	/**
	 * @param IContainer $parent
	 * @param string $name
	 * @param Form $form
	 */
	public function __construct(IContainer $parent = NULL, $name = NULL, Form $form = NULL)
	{
		if ($parent instanceof Form) {
			$this->form = $parent;
		} else {
			if (!is_null($form)) {
				$this->form = $form;
			} else {
				throw new \InvalidArgumentException('You must pass form reference');
			}
		}

		parent::__construct($parent, $name);
	}

	/**
	 * @return Nette\Utils\Html
	 */
	public function getControl()
	{
		$token = str_replace('.', '', uniqid(NULL, TRUE));
		$this->uploaderOptions['url'] = $this->link('PluploadFile', array('token' => $token));
		$this->uploaderOptions['max_file_size'] = $this->returnToBytes(ini_get('upload_max_filesize'));
		$dropZone = Nette\Utils\Html::el('div class=np-drop-zone id=' . 'np-drop-zone-' . uniqid());
		$dropZone->add(Nette\Utils\Html::el('div class=np-drop-zone-files'));
		$dropZone->add(Nette\Utils\Html::el('div class=np-drop-zone-text')->setText('Your javascript is turned off.'));
		$addFileButton = Nette\Utils\Html::el(sprintf('a href=# class="np-add-file" id=%s', 'np-add-file-' . uniqid()))
			->setText('Add files');

		$attributes = array(
			'class' => 'nette-plupload',
			'data-uploader-options' => $this->uploaderOptions
		);

		$wrapperControl = Nette\Utils\Html::el('div', $attributes);
		$wrapperControl->add($addFileButton);
		$wrapperControl->add($dropZone);
		$wrapperControl->add(Nette\Utils\Html::el('input type=hidden name="' . $this->getName() . '" value=' . $token));

		return $wrapperControl;
	}

	/**
	 * 128M = 128 000 000 bytes
	 *
	 * @param $value
	 * @return int|string
	 */
	private function returnToBytes($value)
	{
		$value = trim($value);
		$last = strtolower($value[strlen($value) - 1]);
		switch ($last) {
			case 'g':
				$value *= 1024;
			case 'm':
				$value *= 1024;
			case 'k':
				$value *= 1024;
		}

		return $value;
	}

	/**
	 * File upload handler. Saves file into upload folder and saves origin name to cache.
	 *
	 * @return void
	 */
	public function handlePluploadFile()
	{
		// $this->getParameter('token') is not working
		if (!array_key_exists($this->getParameterId('token'), $_GET)) {
			$this->presenter->sendJson(array('status' => 'error', 'message' => 'Token is missing.'));
		};

		$token = $_GET[$this->getParameterId('token')];
		if (empty($_FILES)) {
			$this->presenter->sendJson(array('File is missing.'));
		}

		$file = new Nette\Http\FileUpload(end($_FILES));
		if (!$file->isOk()) {
			$this->presenter->sendJson(array('status' => 'error', 'message' => $this->getError($file->getError())));
		}

		$tempName = uniqid('np-') . '.' . pathinfo($file->getName(), PATHINFO_EXTENSION);
		$file->move($this->form->getUploadDir() . '/' . $tempName);
		$files = $this->form->cache->load($token, function () {
			return array();
		});

		$files[] = $file;
		$this->form->cache->save($token, $files);
		$this->presenter->sendJson(array('status' => 'success'));
	}

	/**
	 * This method will be called when the component becomes attached to Form.
	 *
	 * @param Nette\ComponentModel\IComponent $something
	 * @return void
	 */
	protected function attached($something)
	{
		if ($this->form instanceof Nette\Application\UI\Form && $this->form->isAnchored() && $this->form->isSubmitted()) {
			$this->loadHttpData();
		}
	}

	/**
	 * Loads HTTP data.
	 * @return void
	 */
	public function loadHttpData()
	{
		$token = $this->form->getHttpData(Form::DATA_TEXT, $this->getName());
		$files = $this->form->getCache()->load($token);
		if (empty($files)) {
			$files = array();
		}

		$this->setValue($files);
	}

	/**
	 * Sets control's value.
	 * @param  mixed
	 * @return void
	 */
	function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * Returns control's value.
	 * @return mixed
	 */
	function getValue()
	{
		return $this->value;
	}

	/**
	 * @return void
	 */
	function validate()
	{
		// TODO: Implement validate() method.
	}

	/**
	 * Returns errors corresponding to control.
	 * @return array
	 */
	function getErrors()
	{
		return array();
	}

	/**
	 * Is control value excluded from $form->getValues() result?
	 * @return bool
	 */
	function isOmitted()
	{
		return FALSE;
	}

	/**
	 * Returns translated string.
	 * @param  mixed
	 * @param  int      plural count
	 * @return string
	 */
	public function translate($value, $count = NULL)
	{
		/** @var Nette\Localization\ITranslator $translator */
		if ($translator = $this->form->getTranslator()) {
			$tmp = is_array($value) ? array(& $value) : array(array(& $value));
			foreach ($tmp[0] as & $v) {
				if ($v != NULL && !$v instanceof Nette\Utils\Html) { // intentionally ==
					$v = $translator->translate($v, $count);
				}
			}
		}
		return $value;
	}

	/**
	 * Sets user-specific option.
	 * @param string $key
	 * @param mixed $value
	 * @return self
	 */
	public function setOption($key, $value)
	{
		if ($value === NULL) {
			unset($this->options[$key]);
		} else {
			$this->options[$key] = $value;
		}
		return $this;
	}

	/**
	 * Returns user-specific option.
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function getOption($key, $default = NULL)
	{
		return isset($this->options[$key]) ? $this->options[$key] : $default;
	}

	/**
	 * Returns user-specific options.
	 * @return array
	 */
	public function getOptions()
	{
		return $this->options;
	}

	/**
	 * @param int $errorCode
	 * @return string
	 */
	private function getError($errorCode)
	{
		switch ($errorCode) {
			case UPLOAD_ERR_INI_SIZE :
				return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
			case UPLOAD_ERR_PARTIAL :
				return 'The uploaded file was only partially uploaded.';
			case UPLOAD_ERR_NO_TMP_DIR :
				return 'Missing a temporary folder.';
			case UPLOAD_ERR_CANT_WRITE :
				return ' Failed to write file to disk';
			case UPLOAD_ERR_EXTENSION :
				return 'A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop';
		}
	}
}