<?php
/**
 * CFileCacheDependency реализует зависимость основаннуею на последней дате модификации файла.
 * Класс выполняет проверку зависимости основанную на дате последней модификации файла (fileName)
 * Зависимость объявляется неизмененной только если дата последней модификации файла не изменялась.
 */
class CFileCacheDependency extends CCacheDependency
{
	/**
	 * @var string имя файла, дата модификации которого используется для проверки изменения зависимости.
	 */
	public $fileName;

	/**
     * Конструктор
	 * @param string имя файла, изменение которого будет проверятся
	 */
	public function __construct($fileName = null)
	{
		$this->fileName = $fileName;
	}

	/**
	 * Формируем данные, необходимые для определения изменения зависимости.
	 * @return mixed последняя дата модификации файла.
	 */
	protected function generateDependentData()
	{
		if($this->fileName!==null)
			return @filemtime($this->fileName);
		else
			throw new \Exception('CFileCacheDependency.fileName не может быть пустым.');
	}
}
