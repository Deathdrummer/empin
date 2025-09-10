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
		
		// Временно отключаем кастомный vertex drag чтобы не конфликтовать
		// paper.on('link:pointerdown', (linkView, evt) => {
		//	this.setupLinkVertexDrag(linkView, evt)
		// })
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
			
			// Используем snap-to-grid из canvas
			const snappedPos = this.canvas.snapToGrid(startPosition.x + deltaX, startPosition.y + deltaY)
			const newX = snappedPos.x
			const newY = snappedPos.y
			
			element.set('position', {
				x: newX,
				y: newY
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

	/**
	 * Настройка кастомного перемещения vertices БЕЗ привязки к сетке
	 * Точно так же как у квадратов - через прямую манипуляцию координат
	 */
	setupLinkVertexDrag(linkView, evt) {
		const paper = this.getPaper()
		const link = linkView.model
		
		// Получаем точку клика
		const clickPoint = paper.clientToLocalPoint(evt.clientX, evt.clientY)
		
		// Добавляем vertex в точке клика
		const currentVertices = link.get('vertices') || []
		const newVertices = [...currentVertices, { x: clickPoint.x, y: clickPoint.y }]
		link.set('vertices', newVertices)
		
		// Индекс новой вершины
		const vertexIndex = newVertices.length - 1
		console.log(`🎯 Добавили vertex ${vertexIndex} в точке`, clickPoint)
		
		let isDragging = false
		let startPoint = clickPoint
		
		const onPointerMove = (moveEvt) => {
			if (!isDragging) return
			
			const currentPoint = paper.clientToLocalPoint(moveEvt.clientX, moveEvt.clientY)
			
			// КЛЮЧЕВОЕ ОТЛИЧИЕ: Прямая манипуляция vertices БЕЗ snap!
			const vertices = link.get('vertices')
			const updatedVertices = vertices.map((vertex, index) => {
				if (index === vertexIndex) {
					return { x: currentPoint.x, y: currentPoint.y }
				}
				return vertex
			})
			
			// Используем link.set() как у квадратов element.set()!
			link.set('vertices', updatedVertices)
		}
		
		const onPointerUp = () => {
			isDragging = false
			paper.el.removeEventListener('pointermove', onPointerMove)
			paper.el.removeEventListener('pointerup', onPointerUp)
			console.log('🏁 Vertex drag завершен')
		}
		
		// Начинаем перетаскивание сразу
		isDragging = true
		paper.el.addEventListener('pointermove', onPointerMove)
		paper.el.addEventListener('pointerup', onPointerUp)
		
		console.log(`🚀 Начали drag vertex ${vertexIndex}`)
	}
}

export default SelectTool