/**
 * ddrDebounceWrap — универсальная debounce-обёртка для любой функции.
 *
 * Позволяет легко оборачивать любые функции с задержкой вызова и гибкими настройками
 * (leading, trailing, maxWait).
 *
 * @param {function} fn         — Функция, которую нужно вызывать с задержкой (дебаунсом)
 * @param {number}   timeout    — Задержка в миллисекундах (по умолчанию 300)
 * @param {object}   options    — Опции debounce:
 *                                - leading  (bool, default: true)  — вызывать на первом срабатывании
 *                                - trailing (bool, default: false) — вызывать на последнем срабатывании
 *                                - maxWait  (number, default: 1000)— максимальная задержка между вызовами
 *
 * @returns {function}          — Обёрнутая функция-дебаунсер (может вызываться с любыми аргументами)
 *
 * -----------------------------
 * Пример использования:
 *
 *   const logDebounced = ddrDebounceWrap(
 *       (x, y) => { console.log('Debounced!', x, y); },
 *       400,
 *       {leading: true, trailing: false, maxWait: 1000}
 *   );
 *
 *   logDebounced('foo', 123); // вызовется один раз через 400мс после последнего вызова
 *
 * -----------------------------
 * Можно использовать для:
 * - событий input/keyup
 * - ajax-запросов
 * - динамического рендера
 * - любых часто срабатывающих функций
 * 
 * 
 * ddrDebounceWrap — универсальная debounce-обёртка для любой функции.
 * @param {function} fn — функция, которую надо вызывать с задержкой
 * @param {number} timeout — задержка в мс (по умолчанию 300)
 * @param {object} options — опции debounce (leading, trailing, maxWait)
 * @return {function} — функция-дебаунсер
 */
export function ddrDebounceWrap(fn, timeout = 300, options = {leading: true, trailing: false, maxWait: 1000}) {
	const {leading = true, trailing = false, maxWait = 1000} = options;
	const debounced = _.debounce(fn, timeout, {leading, trailing, maxWait});
	return function(...args) {
		return debounced.apply(this, args);
	};
}









/**
 * ddrRenderWrap — универсальная JS-обёртка для асинхронного рендера шаблонов с поддержкой flat-раскрытия, computed/async переменных и post-processing.
 * 
 * @param {Object} options — объект настроек
 * @param {string} options.template — Имя шаблона для ddrRender (обязательно)
 * @param {Array} options.map — Массив описания переменных для шаблона:
 *    - строка: обычная переменная (`'key'`)
 *    - flat-строка: раскрытие всех полей объекта (`'...key'`)
 *    - объект: кастомная переменная `{key: fn/const/Promise}` или flat через ключ `'...key'`
 * @param {Function} [options.middleware] — Пост-обработчик mappingData после map, сигнатура:
 *      (selector, mappingData, abortCtrl) => mappingData | false
 *      (возврати false чтобы отменить рендер)
 * @param {Function} options.render — Колбек после рендера шаблона:
 *      (selector, html, mappingData) => void
 * @param {number} [options.timeout=300] — debounce задержка в мс
 * @param {boolean} [options.leading=true] — debounce: сразу на первый вызов
 * @param {boolean} [options.trailing=false] — debounce: на последний вызов
 * @param {number} [options.maxWait=1000] — debounce: максимальная задержка в мс
 *
 * Пример использования:
 * 
 * $.showContract = ddrRenderWrap({
 *     template: 'contract.card',
 *     map: [
 *         'teamId',
 *         '...contract', // flat — все поля объекта contract как отдельные переменные
 *         {title: (selector, args) => args[2].toUpperCase()},
 *         {'...user': async (selector, args) => await apiGetUser(args[3])},
 *         'manager'
 *     ],
 *     middleware: (selector, mappingData, abortCtrl) => {
 *         if (!mappingData.teamId) return false;
 *         mappingData.label = `[${mappingData.id}] ${mappingData.title}`;
 *         mappingData.isBigTeam = mappingData.teamId > 100;
 *         return mappingData;
 *     },
 *     render: (selector, html, mappingData) => {
 *         $(selector).html(html);
 *         console.log('mappingData:', mappingData);
 *     },
 *     timeout: 300,
 *     leading: true,
 *     trailing: false,
 *     maxWait: 1000
 * });
 * 
 * // Вызов:
 * // $.showContract(this, 1, {id:123, title:'Объект'}, 'строка', userObj, 'Иван')
 * // mappingData в шаблоне:
 * // {
 * //   teamId: 1,
 * //   id: 123, title: 'Объект', // (flat contract)
 * //   title: 'СТРОКА',           // (computed в map)
 * //   ...userObj,               // (flat user)
 * //   manager: 'Иван',
 * //   label: '[123] Объект',    // (computed в middleware)
 * //   isBigTeam: false
 * // }
 *
 * --- Варианты элементов map ---
 *   - 'key'            → mappingData.key = args[N]
 *   - '...key'         → flat-раскрытие всех полей объекта args[N]
 *   - {foo: fn/const}  → mappingData.foo = fn(selector, args) | const | await Promise
 *   - {'...foo': ...}  → flat-раскрытие результата fn/Promise/const в mappingData
 *
 * Все типы args автокастятся к числу/boolean/строке.
 * Middleware может мутировать mappingData и возвращать новые поля (computed/валидация).
 * Flat/spread по '...key' возможен в строке и объекте. Если не объект — warning.
 */
export function ddrRenderWrap(options) {
	const {
		template,
		map = [],
		actions,
		middleware,
		render,
		timeout = 300,
		leading = true,
		trailing = false,
		maxWait = 1000
	} = options;

	const debounceParams = { leading, trailing, maxWait };
	
	
	const eventAttrMap = {
	    'click': '[data-ddr-click], [ddr-click]',
	    'input': '[data-ddr-input], [ddr-input]',
	    'change': '[data-ddr-change], [ddr-change]',
	    // Добавь другие нужные события по аналогии!
	};
	

	function autocast(val) {
		if (typeof val === 'boolean' || typeof val === 'number' || val == null) return val;
		if (typeof val !== 'string') return val;
		if (val === 'true') return true;
		if (val === 'false') return false;
		if (val.trim() === '') return val;
		if (!isNaN(val)) return val.indexOf('.') > -1 ? parseFloat(val) : parseInt(val, 10);
		return val;
	}

	const handler = _.debounce(async function(selector, ...args) {
		const abortCtrl = new AbortController();
		let castedArgs = args.map(autocast);

		// --- Формируем mappingData по map (support flat '...key') ---
		const mappingData = {};
		let argIndex = 0;
		for (const item of map) {
			if (typeof item === 'string') {
				if (item.startsWith('...')) {
					const value = castedArgs[argIndex++];
					if (value && typeof value === 'object' && !Array.isArray(value)) {
						Object.assign(mappingData, value);
					} else {
						console.warn(`[ddrRenderWrap] flat '${item}': аргумент не объект, flat проигнорирован`, value);
					}
				} else {
					mappingData[item] = castedArgs[argIndex++];
				}
			} else if (typeof item === 'object' && !Array.isArray(item) && item !== null) {
				for (const [key, value] of Object.entries(item)) {
					if (key.startsWith('...')) {
						let obj;
						if (typeof value === 'function') {
							obj = await value(selector, castedArgs, abortCtrl);
						} else {
							obj = value;
						}
						if (obj && typeof obj === 'object' && !Array.isArray(obj)) {
							Object.assign(mappingData, obj);
						} else {
							console.warn(`[ddrRenderWrap] flat '${key}': аргумент не объект, flat проигнорирован`, obj);
						}
					} else {
						let val;
						if (typeof value === 'function') {
							val = await value(selector, castedArgs, abortCtrl);
						} else if (value && typeof value.then === 'function') {
							val = await value;
						} else {
							val = value;
						}
						mappingData[key] = val;
					}
				}
			}
		}

		// --- После map вызываем middleware с готовыми данными ---
		let finalData = mappingData;
		if (typeof middleware === 'function') {
			const mwResult = await middleware(selector, mappingData, abortCtrl);
			if (mwResult === false) return;
			if (mwResult && typeof mwResult === 'object') finalData = mwResult;
		}

		const html = await ddrRenderWithEvents(template, finalData);

		if (typeof render === 'function') {
			let $html = $(html); // это твой корневой элемент, jQuery-объект

			if (actions && typeof actions === 'object') {
				$html.find('[ddr-action]').each(function() {
					const $el = $(this);
					const actionAttr = $el.attr('ddr-action');
					if (!actionAttr) return;

					const actionDefs = actionAttr.split('|').map(a => a.trim()).filter(Boolean);

					actionDefs.forEach(def => {
					    const [name, params] = def.split(':');

					    // 1. Сокращённая запись: 'name:event1,event2'
					    Object.entries(actions).forEach(([actionKey, handler]) => {
					        const [keyName, eventsStr] = actionKey.split(':');
					        if (keyName !== name) return;
					        if (eventsStr) {
					            const events = eventsStr.split(',').map(e => e.trim());
					            events.forEach(event => {
					                // === Универсальный фильтр ===
					                if (eventAttrMap[event] && $el.is(eventAttrMap[event])) return;

					                $el.off(event + '.ddrAction.' + name)
					                   .on(event + '.ddrAction.' + name, function(evn) {
					                        const argsArr = params ? params.split(',').map(s => s.trim()) : [];
					                        handler(this, {
					                            root: $html,
					                            args: argsArr,
					                            e: evn,
					                            map: finalData
					                        });
					                   });
					            });
					        }
					    });

					    // 2. Старый стиль: actions[name] — массив или объект
					    const actionCfg = actions[name];
					    if (!actionCfg) return;
					    const actionList = Array.isArray(actionCfg) ? actionCfg : [actionCfg];
					    actionList.forEach((item, idx) => {
					        if (!item || typeof item.action !== 'function') return;
					        // === Универсальный фильтр ===
					        if (eventAttrMap[item.event] && $el.is(eventAttrMap[item.event])) return;

					        $el.off(item.event + '.ddrAction.' + name + idx)
					           .on(item.event + '.ddrAction.' + name + idx, function(evn) {
					                const argsArr = params ? params.split(',').map(s => s.trim()) : [];
					                item.action(this, {
					                    root: $html,
					                    args: argsArr,
					                    e: evn,
					                    map: finalData
					                });
					           });
					    });
					});


					
					
				});
			}
			
			
			// передаём $html (jQuery-объект) — так render вставляет уже готовый DOM
			return render(selector, $html, finalData);
		}
		
	}, timeout, debounceParams);

	return function(selector, ...args) {
		return handler.call(this, selector, ...args);
	};
}

















/**
 * ddrOneWrap — обёртка для jQuery .one, с автоперевешиванием, исключениями и уникальным namespace.
 *
 * Пример использования:
 *
 * $(teamSelector).one(
 *     tapEvent + '.teams',
 *     ddrOneWrap(
 *         ['[searchinput]'],
 *         (currentTarget, e, ns) => {
 *             $(currentTarget).removeClass('timesheetcard__team-wait');
 *             $(teamSelector).find('[search]').removeClass('timesheetcard__search-visible');
 *             $(currentTarget).removeAttrib('noswipe');
 *             // ns — уникальный namespace текущего обработчика
 *         }
 *     )
 * );
 *
 * ------------------- Параметры -------------------
 * excludeSelectors  — (array) список CSS-селекторов, по которым обработчик игнорируется и перевешивается заново
 * callback          — (function(currentTarget, e, ns)) функция, вызываемая при срабатывании (если не исключение)
 *                      - currentTarget: элемент, на который вешалось событие
 *                      - e: событие
 *                      - ns: уникальный namespace обработчика (строка вида '.ddrone_abc123')
 *
 * ------------------- Как работает -------------------
 * - Если событие пришло по элементу, попадающему под любой excludeSelector,
 *   обработчик автоматически перевешивается на этот же элемент с тем же уникальным namespace.
 * - Если событие не попадает под исключения — вызывается callback.
 * - Можно использовать стрелочные функции и обычные функции в качестве callback.
 * - Каждый вызов ddrOneWrap генерирует свой уникальный namespace для изоляции обработчиков.
 *
 * ------------------- Применение -------------------
 * - Для кейсов, когда нужно "однократное" событие, но по некоторым элементам
 *   (например, по инпутам/иконкам) действие должно игнорироваться и перевешиваться.
 * - Полностью безопасно для повторного использования на одном элементе с разными логиками.
 *
 * ------------------- Пример -------------------
 * $(el).one('click', ddrOneWrap(['input, .icon'], (currentTarget, e, ns) => {
 *     // логика, если клик не по input/.icon
 * }));
 */

// ddrOneWrap — универсальное решение с поддержкой исключения даже при mouseup/click вне исключения,
// если mousedown был на исключении или его потомке

export function ddrOneWrap(excludeSelectors, callback) {
	const ns = '.ddrone_' + Math.random().toString(36).slice(2, 10);
	let downOnExclude = false;

	// Отслеживаем mousedown/touchstart глобально
	function isOnExclude(e) {
		for (const sel of excludeSelectors) {
			if ($(e.target).closest(sel).length) {
				return true;
			}
		}
		return false;
	}

	document.addEventListener('mousedown', function(e) {
		downOnExclude = isOnExclude(e);
	}, true);
	document.addEventListener('touchstart', function(e) {
		downOnExclude = isOnExclude(e);
	}, true);

	// После mouseup/touchend обязательно сбрасываем флаг
	document.addEventListener('mouseup', function() {
		downOnExclude = false;
	}, true);
	document.addEventListener('touchend', function() {
		downOnExclude = false;
	}, true);

	const handler = function(e) {
		if (downOnExclude) {
			// Если был mousedown/touchstart на исключении, событие игнорируем
			const type = e.type.replace(/\..*$/, '');
			$(e.currentTarget).one(type + ns, handler);
			return;
		}
		callback(e.currentTarget, e, ns);
	};
	return handler;
}












/**
 * ddrInpWrap — debounce-обёртка для input-событий с поддержкой async/await, отмены запросов и авто-мемоизацией value.
 *
 * === Пример использования ===
 * 
 * // В шаблоне:
 * <input data-ddr-input="teamSearchContracts:rool">
 * 
 * // В коде:
 * $.teamSearchContracts = ddrInpWrap(async (inp, [rool], e, abortCtrl) => {
 *     inp.setAttribute('disabled', 'disabled');
 *     try {
 *         const {data, error} = await axiosQuery('get', '/api/search', {}, 'json', abortCtrl);
 *         // обработка результата
 *     } catch (err) {
 *         // обработка ошибок, в т.ч. отмены
 *     } finally {
 *         inp.removeAttribute('disabled');
 *     }
 * }, 200);
 *
 * === Как это работает ===
 * - В обработчик первым аргументом приходит DOM-элемент input.
 * - Вторым — массив аргументов из data-ddr-input (например, [rool]).
 * - Третьим — оригинальное событие input (если нужно).
 * - Четвёртым — объект AbortController для отмены async-запросов.
 *
 * === Особенности и гарантии ===
 * - Вызов твоей функции происходит только если inp.value действительно изменился.
 * - Если пользователь вводит быстро — предыдущее async-обращение отменяется через AbortController.
 * - Ты не создаёшь AbortController вручную — всё под капотом ddrInpWrap.
 * - Обработчик не вызывается на заблокированном поле (disabled).
 * - Поддерживает и sync, и async функции (await внутри можно использовать как угодно).
 * - Дебаунс для каждого input отдельный и независимый.
 *
 * === Как работает делегат ===
 * В data-ddr-input параметры передаются через запятую, парсятся в массив и передаются вторым аргументом твоей функции:
 *   $[func](this, [foo, bar], e)
 *
 * === Пример делегата ===
 * $dom.on('input', '[data-ddr-input]', function(e) {
 *   ...
 *   const argsArr = parseParams(params); // вернёт массив аргументов
 *   $[func](this, argsArr, e);
 * });
 */

export function ddrInpWrap(callback, timeout = 150) {
	const debounceMap = new WeakMap();
	const valueMap = new WeakMap();
	const abortMap = new WeakMap();

	return function(inp, argsArr = [], ...rest) {
		if (inp.disabled) return;
		const lastValue = valueMap.get(inp);
		if (lastValue === inp.value) return;
		valueMap.set(inp, inp.value);
		let debounced = debounceMap.get(inp);
		if (!debounced) {
			debounced = _.debounce(async function(...cbArgs) {
				let abort = abortMap.get(inp);
				if (abort) abort.abort();
				abort = new AbortController();
				abortMap.set(inp, abort);
				try {
					const res = callback.call(inp, cbArgs[0], cbArgs[1] || [], ...cbArgs.slice(2), abort);
					if (res && typeof res.then === 'function') {
						await res;
					}
				} catch (err) {
					if (window?.console) console.error('ddrInpWrap error', err);
				}
			}, timeout);
			debounceMap.set(inp, debounced);
		}
		debounced.call(inp, inp, argsArr, ...rest);
	};
}
