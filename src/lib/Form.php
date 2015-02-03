<?php
namespace Doublemcz\NettePlupload;

use Nette;

class Form extends Nette\Application\UI\Form
{
	/**
	 * @var string
	 */
	public $uploadDir = '';

	/** @var Nette\Caching\Cache */
	public $cache = NULL;

	/**
	 * @param Nette\ComponentModel\IContainer $parent
	 * @param null $name
	 * @param string $uploadDir
	 */
	public function __construct(Nette\ComponentModel\IContainer $parent = NULL, $name = NULL, $uploadDir = "")
	{
		$this->uploadDir = $uploadDir;
		parent::__construct($parent, $name);
	}

	/**
	 * This method will be called when the component (or component's parent)
	 * becomes attached to a monitored object. Do not call this method yourself.
	 * @param  Nette\ComponentModel\IComponent
	 * @return void
	 */
	protected function attached($presenter)
	{
		parent::attached($presenter);
		if (empty($this->uploadDir)) {
			$this->cache = new Nette\Caching\Cache($this->presenter->context->getService('cacheStorage'), 'nette-plupload');
			$this->uploadDir = $this->presenter->context->expand('%tempDir%') . '/nette-plupload-files';
			if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0775, TRUE)) {
				throw new \RuntimeException(sprintf('Cannot create upload dir %s', $this->uploadDir));
			}
		}
	}

	/**
	 * Adds Plupload control
	 *
	 * @param string $name
	 * @return Nette\Forms\Controls\TextInput
	 */
	public function addPlupload($name)
	{
		$control = new FormControl($this, $name, $this);
		return $control;
	}

	/**
	 * @return string
	 */
	public function getUploadDir()
	{
		return $this->uploadDir;
	}

	/**
	 * @return Nette\Caching\Cache
	 */
	public function getCache()
	{
		return $this->cache;
	}
}