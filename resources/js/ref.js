// ref.js

// Убедитесь, что Lodash (_) доступен глобально, или импортируйте его, если используете модули.
// import _ from 'lodash'; 

// Вспомогательные символы для хранения обработчиков
const getHandlers = Symbol('getHandlers');
const setHandlers = Symbol('setHandlers');

// Вспомогательная функция для проверки JSON (используется в ddrStore)
function _isJsonStringInternal(str) {
    if (typeof str !== 'string') return false;
    try {
        JSON.parse(str);
        return true;
    } catch (e) {
        return false;
    }
}

/**
 * Функция для работы с localStorage.
 * @param {string} key - Ключ.
 * @param {*} [value] - Значение.
 * @returns {*|boolean} - Извлеченное значение, null если не найдено, true/false для операций записи/удаления.
 */
window.ddrStore = function(key, value) {
    if (!key || typeof key !== 'string') {
        console.error('ddrStore: Ключ должен быть непустой строкой.');
        return false;
    }

    if (value === false) { // Удаление
        try {
            localStorage.removeItem(key);
            return true;
        } catch (e) {
            console.error('ddrStore: Ошибка при удалении из localStorage:', e);
            return false;
        }
    } else if (value !== undefined) { // Запись
        let valueToStore = value;
        if (typeof valueToStore === 'object' && valueToStore !== null) {
            try {
                valueToStore = JSON.stringify(valueToStore);
            } catch (e) {
                console.error('ddrStore: Ошибка при JSON.stringify объекта:', e);
                return false;
            }
        }
        try {
            localStorage.setItem(key, valueToStore);
            return true;
        } catch (e) {
            console.error('ddrStore: Ошибка при записи в localStorage (возможно, переполнение):', e);
            return false;
        }
    } else { // Чтение
        try {
            let getValue = localStorage.getItem(key);
            if (getValue === null) return null; // Ключ не найден
            if (_isJsonStringInternal(getValue)) {
                try {
                    return JSON.parse(getValue);
                } catch (e) {
                    // Если это была невалидная JSON строка
                    console.warn('ddrStore: Значение по ключу было похоже на JSON, но не распарсилось. Возвращено как строка.', key, getValue);
                    return getValue; 
                }
            }
            return getValue; // Возвращаем как есть (может быть числом или булевым значением, сохраненным как строка)
        } catch (e) {
            console.error('ddrStore: Ошибка при чтении из localStorage:', e);
            return null;
        }
    }
};


/**
 * Создает прокси для массива для отслеживания мутаций.
 * @param {Array} originalArray - Исходный массив.
 * @param {string} arrayPropertyKey - Имя свойства в parentObject, которое содержит этот массив.
 * @param {object} parentObject - Непосредственный объект-владелец этого массива.
 * @param {object} rootWatcherInstance - Экземпляр ddrWatcher самого верхнего уровня.
 * @returns {Proxy} Проксированный массив.
 */
function createArrayProxy(originalArray, arrayPropertyKey, parentObject, rootWatcherInstance) {
    const arrayMutatingMethods = ['push', 'pop', 'shift', 'unshift', 'splice', 'fill', 'sort', 'reverse'];

    return new Proxy(originalArray, {
        get(targetArr, prop, receiver) {
            const value = Reflect.get(targetArr, prop, receiver);

            if (typeof value === 'function' && arrayMutatingMethods.includes(prop)) {
                return function(...args) {
                    const arraySnapshotBeforeMutation = [...targetArr];
                    const result = value.apply(targetArr, args);

                    if (rootWatcherInstance && rootWatcherInstance[setHandlers] && rootWatcherInstance[setHandlers].length > 0) {
                        rootWatcherInstance[setHandlers].forEach(handler =>
                            handler({
                                type: 'set',
                                target: parentObject,
                                prop: arrayPropertyKey,
                                value: targetArr,
                                oldValue: arraySnapshotBeforeMutation,
                                mutationInfo: { method: prop, args: args }
                            })
                        );
                    }

                    if (rootWatcherInstance && rootWatcherInstance.ddrStoreKey && typeof rootWatcherInstance.ddrStoreKey === 'string') {
                        try {
                            const cleanData = rootWatcherInstance.all();
                            window.ddrStore(rootWatcherInstance.ddrStoreKey, cleanData);
                        } catch (e) {
                            console.error("Ошибка в createArrayProxy (метод массива) при сохранении:", e);
                        }
                    }
                    return result;
                };
            }
            // Если необходима глубокая реактивность для элементов массива (объектов/других массивов):
            // if (_.isPlainObject(value)) return window.ddrWatcher(value, null /* funcsObj для детей? */, null, rootWatcherInstance);
            // if (Array.isArray(value) && value !== targetArr) return createArrayProxy(value, prop, targetArr, rootWatcherInstance);
            return value;
        },
        set(targetArr, indexOrProp, value, receiver) {
            const oldValueAtIndex = targetArr[indexOrProp];
            const arraySnapshotBeforeMutation = [...targetArr];
            const success = Reflect.set(targetArr, indexOrProp, value, receiver);

            if (success) {
                if (rootWatcherInstance && rootWatcherInstance[setHandlers] && rootWatcherInstance[setHandlers].length > 0) {
                    rootWatcherInstance[setHandlers].forEach(handler =>
                        handler({
                            type: 'set',
                            target: parentObject,
                            prop: arrayPropertyKey,
                            value: targetArr,
                            oldValue: arraySnapshotBeforeMutation,
                            mutationInfo: { property: indexOrProp, newValue: value, oldValue: oldValueAtIndex }
                        })
                    );
                }
                if (rootWatcherInstance && rootWatcherInstance.ddrStoreKey && typeof rootWatcherInstance.ddrStoreKey === 'string') {
                     try {
                        const cleanData = rootWatcherInstance.all();
                        window.ddrStore(rootWatcherInstance.ddrStoreKey, cleanData);
                    } catch (e) {
                        console.error("Ошибка в createArrayProxy (set индекс/свойство) при сохранении:", e);
                    }
                }
            }
            return success;
        }
    });
}

window.ddrWatcher = function (objToWatch, funcsObj = null, storeKeyFromUser = false, _rootWatcherInternalContext = null) {
    const currentProxedData = _.isPlainObject(objToWatch) ? objToWatch : { value: objToWatch };
    const rootInstance = _rootWatcherInternalContext || currentProxedData;

    if (!_rootWatcherInternalContext) {
        currentProxedData.ddrStoreKey = (typeof storeKeyFromUser === 'string' && storeKeyFromUser) ? storeKeyFromUser : null;
        currentProxedData[getHandlers] = [];
        currentProxedData[setHandlers] = [];

        if (typeof currentProxedData.all !== 'function') {
            currentProxedData.all = function () {
                const cleanObject = obj => {
                    if (Array.isArray(obj)) {
                        return obj.map(item => (_.isPlainObject(item) || Array.isArray(item) ? cleanObject(item) : item));
                    } else if (_.isPlainObject(obj)) {
                        return Object.fromEntries(
                            Object.entries(obj)
                            .filter(([key]) => !(key === 'ddrStoreKey' || key === 'all' || key === 'observe' || typeof key === 'symbol' || (typeof obj[key] === 'function' && (key === 'all' || key === 'observe'))))
                            .map(([key, value]) => [key,_.isPlainObject(value) || Array.isArray(value) ? cleanObject(value) : value])
                        );
                    } return obj;
                };
                const dataToClean = {...this}; 
                delete dataToClean[getHandlers]; delete dataToClean[setHandlers];
                delete dataToClean.all; delete dataToClean.observe; delete dataToClean.ddrStoreKey;
                return cleanObject(dataToClean);
            };
        }
        if (typeof currentProxedData.observe !== 'function') {
            currentProxedData.observe = function (funcs) {
                let g, s, m;
                if (_.isFunction(funcs)) m = funcs;
                else if (_.isPlainObject(funcs)) { g = funcs?.get; s = funcs?.set; }
                if (m) { this[getHandlers].push(m); this[setHandlers].push(m); }
                if (g) this[getHandlers].push(g); if (s) this[setHandlers].push(s);
            };
        }
        if (funcsObj) currentProxedData.observe(funcsObj);
        
        if (currentProxedData.ddrStoreKey) {
            const storedData = window.ddrStore(currentProxedData.ddrStoreKey);
            if (storedData !== null) {
                if (typeof storedData === 'object' && storedData !== null) {
                    Object.keys(storedData).forEach(key => {
                        if (Object.prototype.hasOwnProperty.call(storedData, key) && 
                            !(key === 'ddrStoreKey' || key === 'all' || key === 'observe' || typeof key === 'symbol' || (typeof currentProxedData[key] === 'function'))) {
                            currentProxedData[key] = storedData[key];
                        }
                    });
                } else if (currentProxedData.hasOwnProperty('value') && !_.isPlainObject(objToWatch) ) {
                    currentProxedData.value = storedData;
                }
            }
        }
    }

    return new Proxy(currentProxedData, {
        get(target, property, receiver) {
            const value = Reflect.get(target, property, receiver);

            if (property === 'isProxy') return true;

            if (typeof property !== 'symbol' && typeof value !== 'function' &&
                rootInstance[getHandlers] && rootInstance[getHandlers].length > 0) {
                rootInstance[getHandlers].forEach(handler =>
                    handler({ type: 'get', target: target, prop: property, value: value })
                );
            }

            if (_.isPlainObject(value)) {
                return window.ddrWatcher(value, funcsObj, null, rootInstance);
            }
            if (Array.isArray(value)) {
                return [...value]; // ⬅️ вот это ключевой момент
            }
            return value;
        },
        set(target, property, value, receiver) {
            const oldValue = target[property];
            // Не вызываем обработчики и сохранение для служебных полей, если они устанавливаются изнутри
            const isInternalField = property === 'ddrStoreKey' || typeof property === 'symbol' || property === 'all' || property === 'observe';
            
            const success = Reflect.set(target, property, value, receiver);

            if (success && !isInternalField) {
                if (rootInstance[setHandlers] && rootInstance[setHandlers].length > 0) {
                    rootInstance[setHandlers].forEach(handler => 
                        handler({ type: 'set', target: target, prop: property, value: value, oldValue: oldValue })
                    );
                }
                if (rootInstance.ddrStoreKey) {
                    try {
                        const cleanData = rootInstance.all();
                        window.ddrStore(rootInstance.ddrStoreKey, cleanData);
                    } catch(e) { 
                        console.error("Ошибка при вызове .all() или ddrStore в ddrWatcher (set):", e);
                    }
                }
            }
            return success;
        },
        ownKeys(target) {
            return Reflect.ownKeys(target).filter(key => 
                !(key === 'ddrStoreKey' || key === 'all' || key === 'observe' || typeof key === 'symbol')
            );
        },
        getOwnPropertyDescriptor(target, property) {
            if (property === 'ddrStoreKey' || property === 'all' || property === 'observe' || typeof property === 'symbol') {
                return undefined;
            }
            return Reflect.getOwnPropertyDescriptor(target, property);
        },
    });
};

window.ddrRef = function(data = null, watchFuncsOrStoreKey = null, storeKeyFromThirdArg = false) {
    let useWatcher = false;
    let effectiveStoreKey = null;

    if (watchFuncsOrStoreKey && typeof watchFuncsOrStoreKey === 'object' && watchFuncsOrStoreKey !== null && !Array.isArray(watchFuncsOrStoreKey) /* убедимся, что это не массив */) {
        useWatcher = true;
        effectiveStoreKey = (typeof storeKeyFromThirdArg === 'string' && storeKeyFromThirdArg) ? storeKeyFromThirdArg : null;
    } else if (typeof watchFuncsOrStoreKey === 'string' && watchFuncsOrStoreKey) {
        useWatcher = false;
        effectiveStoreKey = watchFuncsOrStoreKey;
    } else if (typeof storeKeyFromThirdArg === 'string' && storeKeyFromThirdArg) {
        useWatcher = false;
        effectiveStoreKey = storeKeyFromThirdArg;
    }

    if (useWatcher) {
        return window.ddrWatcher(data, watchFuncsOrStoreKey, effectiveStoreKey, null);
    } else {
        let targetObject;
        if (effectiveStoreKey) {
            const dataFromStorage = window.ddrStore(effectiveStoreKey);
            if (dataFromStorage !== null) {
                targetObject = dataFromStorage;
            } else {
                targetObject = _.isPlainObject(data) ? data : { value: data };
                window.ddrStore(effectiveStoreKey, targetObject); 
            }
        } else {
            targetObject = _.isPlainObject(data) ? data : { value: data };
        }

        const proxyHandlers = {
            get(target, prop, receiver) {
                if (prop === 'isProxy') return false;
                if (prop in target) {
                    const value = Reflect.get(target, prop, receiver);
                    if (_.isNumber(value)) return Number(value); // Ваша логика для чисел
                    return value;
                } else { 
                    return null; // Ваша логика для несуществующих свойств
                }
            }
        };

        if (effectiveStoreKey) {
            proxyHandlers.set = function(target, prop, value, receiver) {
                const success = Reflect.set(target, prop, value, receiver);
                if (success) { 
                    window.ddrStore(effectiveStoreKey, target); 
                }
                return success;
            };
        }
        return new Proxy(targetObject, proxyHandlers);
    }
};