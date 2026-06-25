import logger from './Logger.js'
import { LinkMetadata, LineStyles, ConnectionTypes } from './LinkMetadata.js'

/**
 * Основной класс для управления холстом рисования
 */
class DrawingCanvas {
	constructor(containerId) {
		this.containerId = containerId
		this.container = null
		this.graph = null
		this.paper = null
		this.lineStyleSelector = null // Будет установлен позже
		this.initialized = false
	}
	
	/**
	 * Установить селектор стилей линий
	 */
	setLineStyleSelector(selector) {
		this.lineStyleSelector = selector
	}

	/**
	 * Инициализация холста
	 */
	init() {
		if (this.initialized) return

		logger.info('Initializing DrawingCanvas')
		
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
		logger.info('DrawingCanvas initialized')
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
			// Создаем стандартную связь без стрелок с мета-информацией
			defaultLink: () => {
				// Получаем текущие настройки из селектора
				const currentSettings = this.lineStyleSelector ? 
					this.lineStyleSelector.getCurrentSettings() : 
					{ color: '#666666', lineStyle: LineStyles.SOLID }
				
				const link = new window.joint.shapes.standard.Link({
					attrs: {
						line: {
							stroke: currentSettings.color,
							strokeWidth: 1,
							targetMarker: 'none', // Убираем стрелку
							sourceMarker: 'none'  // Убираем стрелку с начала тоже
						}
					},
				})
				
				// Добавляем мета-информацию с текущими настройками
				new LinkMetadata(link, {
					color: currentSettings.color,
					lineStyle: currentSettings.lineStyle,
					connectionType: ConnectionTypes.SHAPE_TO_SHAPE
				})
				
				return link
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
				logger.log('validateMagnet:', magnet.getAttribute('magnet'))
				return magnet.getAttribute('magnet') === 'true'
			},
			// Валидация подключений - только через порты с магнитами  
			validateConnection: (cellViewS, magnetS, cellViewT, magnetT, end, linkView) => {
				logger.log('validateConnection:', { magnetS, magnetT })
				
				// Проверяем что оба конца подключены к магнитам (портам)
				if (!magnetS || !magnetT) {
					logger.log('Connection rejected: not connected to magnets')
					return false
				}
				
				// Не разрешаем подключение к одному элементу
				if (cellViewS === cellViewT) {
					logger.log('Connection rejected: same element')
					return false
				}
				
				logger.log('Connection approved!')
				return true
			}
		})
		
		// Обработчики событий для линков
		this.graph.on('add', (cell) => {
			if (cell.isLink()) {
				this._onLinkAdded(cell)
			}
		})
		
		this.graph.on('change:vertices change:source change:target', (link) => {
			if (link.isLink()) {
				this._onLinkChanged(link)
			}
		})
		
		logger.info('Paper created with port-based linking enabled')
	}
	
	/**
	 * Обработчик добавления линка
	 */
	_onLinkAdded(link) {
		// Пока ничего не делаем - ждём полного подключения
	}
	
	/**
	 * Обработчик изменения линка
	 */
	_onLinkChanged(link) {
		// Проверяем, если линк полностью подключен к двум элементам
		const source = link.source()
		const target = link.target()
		
		if (source.id && target.id) {
			// Линк полностью подключен - определяем и выводим тип
			const sourceElement = this.graph.getCell(source.id)
			const targetElement = this.graph.getCell(target.id)
			const connectionType = LinkMetadata.detectConnectionType(sourceElement, targetElement)
			
			// Обновляем метаданные с правильным типом
			const metadata = LinkMetadata.fromLink(link)
			if (metadata) {
				metadata.setConnectionType(connectionType)
				console.log('🔗 Тип соединения линии:', connectionType)
			}
		}
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
		
		logger.log('Updating paper size:', containerWidth, 'x', containerHeight)
		
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