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


### План развития
См. **ROADMAP.md** - детальный план реализации выносок, соединительных линий, направляющих, дополнительных фигур и других функций.

- КМ - контекстное меню
- используй документацию jointjs 4.1.3 https://docs.jointjs.com/api