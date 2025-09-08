import BaseTool from './BaseTool.js'

/**
 * Инструмент выделения и перемещения холста
 */
class SelectTool extends BaseTool {
	constructor(name, canvas, eventManager) {
		super(name, canvas, eventManager)
	}

	/**
	 * Активация инструмента
	 */
	onActivate() {
		// Устанавливаем курсор по умолчанию
		this.getPaper().el.style.cursor = 'default'
		console.log('SelectTool активирован')
	}

	/**
	 * Деактивация инструмента
	 */
	onDeactivate() {
		// Сбрасываем курсор
		this.getPaper().el.style.cursor = 'default'
	}

	/**
	 * Обработка клика по холсту
	 */
	onCanvasClick(point, event) {
		// Снимаем выделение со всех элементов при клике по пустому месту
		this.getGraph().getCells().forEach(cell => {
			cell.set('selected', false)
			cell.attr('body/stroke', '#cfcccc')
		})
	}

	/**
	 * Обработка клика по элементу
	 */
	onElementClick(element, event) {
		// Логика выделения элемента уже реализована в EventManager
	}
}

export default SelectTool