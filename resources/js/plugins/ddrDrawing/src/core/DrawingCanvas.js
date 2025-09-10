/**
 * Основной класс для управления холстом рисования
 */
class DrawingCanvas {
	constructor(containerId) {
		this.containerId = containerId
		this.container = null
		this.graph = null
		this.paper = null
		this.initialized = false
	}

	/**
	 * Инициализация холста
	 */
	init() {
		if (this.initialized) return

		console.log('Initializing DrawingCanvas')
		
		// Проверяем JointJS
		if (!window.joint) {
			throw new Error('JointJS library not found')
		}
		
		// Находим контейнер
		this.container = document.querySelector(this.containerId)
		if (!this.container) {
			throw new Error(`Canvas container ${this.containerId} not found`)
		}
		
		this._createGraph()
		this._createPaper()
		this._updatePaperSize()
		
		this.initialized = true
		console.log('DrawingCanvas initialized')
	}

	/**
	 * Создание графа
	 */
	_createGraph() {
		this.graph = new window.joint.dia.Graph()
	}

	/**
	 * Создание paper
	 */
	_createPaper() {
		this.paper = new window.joint.dia.Paper({
			el: this.container,
			model: this.graph,
			width: '100%',
			height: '100%',
			gridSize: 1, // Пиксельная точность для плавного движения линий
			drawGridSize: 10, // Визуальная сетка каждые 10px
			drawGrid: {
				name: 'dot',
				args: {
					color: '#cccccc',
					thickness: 1
				}
			},
			background: {
				color: '#fdfdfd'
			},
			// ВАЖНО: отключаем встроенное движение элементов, используем кастомную логику
			interactive: {
				elementMove: false,  // Отключаем встроенное движение - используем кастомную логику в SelectTool
				addLinkFromMagnet: true, // Разрешаем создание связей от портов
				linkMove: true,
				vertexMove: true,
				vertexAdd: true,
				vertexRemove: true,
				arrowheadMove: true,
				labelMove: false,
				useLinkTools: true
			},
			// Разрешаем создание связей только через порты
			linkPinning: false,
			// Создаем стандартную связь без стрелок с удлинением на радиус порта
			defaultLink: () => {
				return new window.joint.shapes.standard.Link({
					attrs: {
						line: {
							stroke: '#666666',
							strokeWidth: 1,
							targetMarker: 'none', // Убираем стрелку
							sourceMarker: 'none'  // Убираем стрелку с начала тоже
						}
					},
				})
			},
			// Настройки магнитного поведения
			magnetThreshold: 'onleave',
			markAvailable: true, // Показывать доступные порты
			// Настройки по умолчанию для всех связей - убираем отступ
			defaultConnectionPoint: {
				name: 'boundary',
				args: {
					offset: -2.5,  // ОТРИЦАТЕЛЬНЫЙ отступ - заходим внутрь элемента
					extrapolate: true
				}
			},
			// Валидация магнитов - разрешаем активные порты
			validateMagnet: (cellView, magnet) => {
				console.log('=== validateMagnet ===', magnet.getAttribute('magnet'))
				return magnet.getAttribute('magnet') === 'true'
			},
			// Валидация подключений - только через порты с магнитами  
			validateConnection: (cellViewS, magnetS, cellViewT, magnetT, end, linkView) => {
				console.log('=== validateConnection ===', { magnetS, magnetT })
				
				// Проверяем что оба конца подключены к магнитам (портам)
				if (!magnetS || !magnetT) {
					console.log('Connection rejected: not connected to magnets')
					return false
				}
				
				// Не разрешаем подключение к одному элементу
				if (cellViewS === cellViewT) {
					console.log('Connection rejected: same element')
					return false
				}
				
				console.log('Connection approved!')
				return true
			}
		})
		
		console.log('=== Paper created with port-based linking enabled ===')
	}

	/**
	 * Обновление размеров paper
	 */
	updatePaperSize() {
		if (!this.paper) return
		
		const container = this.paper.el
		if (!container || !container.offsetParent) {
			setTimeout(() => this.updatePaperSize(), 100)
			return
		}
		
		const containerWidth = container.offsetWidth
		const containerHeight = container.offsetHeight
		
		console.log('Updating paper size:', containerWidth, 'x', containerHeight)
		
		this.paper.setDimensions(containerWidth, containerHeight)
		this.paper.render()
	}

	/**
	 * Приватный метод для немедленного обновления размеров
	 */
	_updatePaperSize() {
		setTimeout(() => this.updatePaperSize(), 50)
	}

	/**
	 * Получить граф
	 */
	getGraph() {
		return this.graph
	}

	/**
	 * Получить paper
	 */
	getPaper() {
		return this.paper
	}

	/**
	 * Установить масштаб
	 */
	setScale(scale) {
		if (this.paper) {
			this.paper.scale(scale, scale)
		}
	}

	/**
	 * Получить текущий масштаб
	 */
	getScale() {
		return this.paper ? this.paper.scale() : { sx: 1, sy: 1 }
	}

	/**
	 * Установить трансляцию
	 */
	setTranslation(tx, ty) {
		if (this.paper) {
			this.paper.translate(tx, ty)
		}
	}

	/**
	 * Получить текущую трансформацию
	 */
	getMatrix() {
		return this.paper ? this.paper.matrix() : null
	}

	/**
	 * Преобразовать координаты клиента в локальные
	 */
	clientToLocalPoint(clientX, clientY) {
		return this.paper ? this.paper.clientToLocalPoint(clientX, clientY) : { x: 0, y: 0 }
	}

	/**
	 * Привязать точку к сетке с учетом масштаба
	 */
	snapToGrid(x, y, gridSize = 10) {
		// Базовый размер сетки НЕ зависит от масштаба!
		// JointJS clientToLocalPoint уже учитывает трансформации
		return {
			x: Math.round(x / gridSize) * gridSize,
			y: Math.round(y / gridSize) * gridSize
		}
	}

	/**
	 * Получить размер сетки
	 */
	getGridSize() {
		return this.paper ? this.paper.options.gridSize : 10
	}
}

export default DrawingCanvas