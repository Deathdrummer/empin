import BaseTool from './BaseTool.js'

/**
 * Инструмент выделения и перемещения элементов
 */
class SelectTool extends BaseTool {
	constructor(name, canvas, eventManager) {
		super(name, canvas, eventManager)
	}

	/**
	 * Активация инструмента
	 */
	onActivate() {
		this.getPaper().el.style.cursor = 'default'
		this.setupEvents()
		console.log('SelectTool активирован')
	}

	/**
	 * Деактивация инструмента
	 */
	onDeactivate() {
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

	/**
	 * Настройка событий
	 */
	setupEvents() {
		const paper = this.getPaper()

		// Обработка кликов по элементам для перемещения
		paper.on('element:pointerdown', (elementView, evt) => {
			this.setupElementDrag(elementView, evt)
		})
	}

	/**
	 * Настройка перемещения элемента
	 */
	setupElementDrag(elementView, evt) {
		const paper = this.getPaper()
		const element = elementView.model
		
		let isDragging = false
		let startPoint = null
		let startPosition = null
		
		const onPointerMove = (evt) => {
			if (!isDragging) return
			
			const currentPoint = paper.clientToLocalPoint(evt.clientX, evt.clientY)
			const deltaX = currentPoint.x - startPoint.x
			const deltaY = currentPoint.y - startPoint.y
			
			element.set('position', {
				x: startPosition.x + deltaX,
				y: startPosition.y + deltaY
			})
		}
		
		const onPointerUp = () => {
			isDragging = false
			paper.el.removeEventListener('pointermove', onPointerMove)
			paper.el.removeEventListener('pointerup', onPointerUp)
		}
		
		// Начинаем перетаскивание
		startPoint = paper.clientToLocalPoint(evt.clientX, evt.clientY)
		startPosition = element.get('position')
		isDragging = true
		
		paper.el.addEventListener('pointermove', onPointerMove)
		paper.el.addEventListener('pointerup', onPointerUp)
	}
}

export default SelectTool