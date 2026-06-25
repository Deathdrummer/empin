# CLAUDE.md

## ddrDrawing Plugin

Плагин для создания чертежей на основе JointJS 4.1.3 в Laravel-приложении.

### Ключевые файлы
- **Код плагина**: `index.js` 
- **Разметка**: `Z:\empin.loc\resources\views\admin\section\system\system.blade.php` (вкладка systemTab7)
- **Стили**: `Z:\empin.loc\resources\sass\admin.sass` (секция `//-- здесь стили дял плагина ddrDrawing`)

### Важные особенности
- **JointJS доступен как `window.joint`** из bootstrap.js - НЕ импортировать заново
- **Отложенная инициализация** для скрытых контейнеров (500ms retry)
- **Контейнер холста**: `#ddrDrawingCanvas`
- **Паттерн плагина**: фабричная функция `ddrDrawing()` возвращает API
- **Инициализация**: `drawingInstance.init()` в blade template (строка 331)


### Система мета-информации для соединительных линий

#### Архитектура
- **LinkMetadata** (`src/core/LinkMetadata.js`) - класс для управления мета-данными линий
- Автоматическое добавление мета-данных при создании линка в DrawingCanvas
- Интеграция с контекстным меню для изменения свойств

#### Типы линий (LineStyles)
- `SOLID` - сплошная
- `DASHED` - штриховая (10 5)
- `DOTTED` - точечная (2 2)  
- `DASH_DOT` - штрих-точка (10 5 2 5)
- `DASH_DOT_DOT` - штрих-две точки (10 5 2 5 2 5)
- `LONG_DASH` - длинный штрих (15 5)
- `SHORT_DASH` - короткий штрих (5 3)

#### Типы соединений (ConnectionTypes)
- `SHAPE_TO_SHAPE` - фигура к фигуре
- `SHAPE_TO_CALLOUT` - фигура к выноске
- `SHAPE_TO_ANNOTATION` - фигура к аннотации
- `SHAPE_TO_DIMENSION` - фигура к размеру
- `SHAPE_TO_PORT` - фигура к порту
- `PORT_TO_PORT` - порт к порту
- `CUSTOM` - пользовательский тип

#### Хранимые данные
- **color** - цвет линии
- **lineStyle** - стиль линии
- **connectionType** - тип соединения (автоопределяется)
- **realLength** - реальная длина в пикселях (автоматически пересчитывается)
- **nominalLength** - номинальная длина в мм (задается пользователем)

#### Использование
```javascript
// Получить метаданные линка
const metadata = LinkMetadata.fromLink(link)

// Изменить свойства
metadata.setLineStyle(LineStyles.DASHED)
metadata.setColor('#ff0000')
metadata.setNominalLength(150)

// Получить данные
const info = metadata.getMetadata()
```

#### Важно о портах
- **Порты универсальные** - один порт может использоваться для любого типа соединения
- Тип соединения определяется автоматически по типам соединяемых элементов
- Метод `LinkMetadata.detectConnectionType()` анализирует элементы при создании линка

### План развития
См. **ROADMAP.md** - детальный план реализации выносок, соединительных линий, направляющих, дополнительных фигур и других функций.

- КМ - контекстное меню
- используй документацию jointjs 4.1.3 https://docs.jointjs.com/api