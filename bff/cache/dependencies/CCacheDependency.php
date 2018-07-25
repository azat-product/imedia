<?php
/**
 * Класс CCacheDependency, реализующий интерфейс ICacheDependency
 * Класс наледник должен перегрузить метод generateDependentData
 */
class CCacheDependency implements ICacheDependency
{
	private $_data;

	/**
     * Определяет зависимость - генерируя и сохраняя данные связанные с зависимостью.
     * Данный метод вызывается непосредственно перед записью данных в кеш.
	 */
	public function evaluateDependency()
	{
		$this->_data = $this->generateDependentData();
	}

	/**
	 * @return boolean изменилась ли зависимость.
	 */
	public function getHasChanged()
	{
		return $this->generateDependentData() != $this->_data;
	}

	/**
	 * @return mixed данные используемые для определения изменения зависимости.
	 * данные доступны после вызова evaluateDependency
	 */
	public function getDependentData()
	{
		return $this->_data;
	}

	/**
	 * Формируем данные, необходимые для определения изменения зависимости.
     * Наследуемые классы должны прегружать данный метод.
	 * @return mixed данные используемые для определения изменения зависимости.
	 */
	protected function generateDependentData()
	{
		return null;
	}
}
