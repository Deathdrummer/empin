#!/usr/bin/env python3
"""
Скрипт сборки CAD-Share.exe
Встраивает необходимые файлы и компилирует в один executable
"""

import base64
import os
import sys
import subprocess
from pathlib import Path

def encode_file_to_base64(file_path):
    """Кодирование файла в base64"""
    try:
        with open(file_path, 'rb') as f:
            return base64.b64encode(f.read()).decode('utf-8')
    except Exception as e:
        print(f"Ошибка кодирования {file_path}: {e}")
        return ""

def download_dependencies():
    """Скачивание необходимых файлов"""
    print("📥 Скачиваем зависимости...")

    # URLs для скачивания
    files_to_download = {
        'mongoose.exe': 'https://mongoose.ws/binary/mongoose-7.18-win64.exe',
        'plink.exe': 'https://the.earth.li/~sgtatham/putty/latest/w64/plink.exe'
    }

    deps_dir = Path('deps')
    deps_dir.mkdir(exist_ok=True)

    for filename, url in files_to_download.items():
        filepath = deps_dir / filename

        if filepath.exists():
            print(f"✅ {filename} уже существует")
            continue

        print(f"⬇️ Скачиваем {filename}...")

        try:
            import urllib.request
            urllib.request.urlretrieve(url, str(filepath))
            print(f"✅ {filename} скачан")
        except Exception as e:
            print(f"❌ Ошибка скачивания {filename}: {e}")
            return False

    return True

def embed_files_in_source():
    """Встраивание файлов в исходный код"""
    print("📦 Встраиваем файлы в исходный код...")

    deps_dir = Path('deps')

    # Кодируем файлы
    mongoose_data = encode_file_to_base64(deps_dir / 'mongoose.exe')
    plink_data = encode_file_to_base64(deps_dir / 'plink.exe')

    if not mongoose_data or not plink_data:
        print("❌ Не удалось закодировать файлы")
        return False

    # Читаем исходный код
    with open('main.py', 'r', encoding='utf-8') as f:
        source_code = f.read()

    # Заменяем заглушки на реальные данные
    source_code = source_code.replace('MONGOOSE_DATA = ""', f'MONGOOSE_DATA = "{mongoose_data}"')
    source_code = source_code.replace('PLINK_DATA = ""', f'PLINK_DATA = "{plink_data}"')

    # Добавляем функцию распаковки
    extraction_code = '''
def extract_embedded_files(self):
    """Распаковка встроенных файлов"""
    try:
        # Распаковываем mongoose.exe
        if MONGOOSE_DATA:
            mongoose_data = base64.b64decode(MONGOOSE_DATA)
            with open(self.mongoose_path, 'wb') as f:
                f.write(mongoose_data)
            os.chmod(self.mongoose_path, 0o755)

        # Распаковываем plink.exe
        if PLINK_DATA:
            plink_data = base64.b64decode(PLINK_DATA)
            with open(self.plink_path, 'wb') as f:
                f.write(plink_data)
            os.chmod(self.plink_path, 0o755)

        self.log("✅ Встроенные файлы распакованы")
        return True

    except Exception as e:
        self.log(f"❌ Ошибка распаковки: {str(e)}")
        return False
'''

    # Вставляем функцию после класса
    source_code = source_code.replace(
        'class CADShareApp:',
        'class CADShareApp:' + extraction_code
    )

    # Заменяем создание пустых файлов на распаковку
    source_code = source_code.replace(
        '''# Создаем пустые файлы для тестирования
            with open(self.mongoose_path, 'w') as f:
                f.write("# mongoose.exe placeholder")
            with open(self.plink_path, 'w') as f:
                f.write("# plink.exe placeholder")''',
        'self.extract_embedded_files()'
    )

    # Сохраняем модифицированный код
    with open('main_embedded.py', 'w', encoding='utf-8') as f:
        f.write(source_code)

    print("✅ Файлы встроены в исходный код")
    return True

def build_executable():
    """Сборка исполняемого файла"""
    print("🔨 Собираем исполняемый файл...")

    try:
        # Проверяем наличие PyInstaller
        subprocess.run(['pyinstaller', '--version'], check=True, capture_output=True)
    except (subprocess.CalledProcessError, FileNotFoundError):
        print("❌ PyInstaller не установлен. Устанавливаем...")
        subprocess.run([sys.executable, '-m', 'pip', 'install', 'pyinstaller'], check=True)

    # Команда сборки
    cmd = [
        'pyinstaller',
        '--onefile',                    # Один файл
        '--windowed',                   # Без консоли
        '--name=CAD-Share',             # Имя файла
        '--icon=icon.ico',              # Иконка (если есть)
        '--clean',                      # Чистая сборка
        '--distpath=.',                 # Папка вывода
        'main_embedded.py'
    ]

    # Убираем --icon если файла нет
    if not Path('icon.ico').exists():
        cmd.remove('--icon=icon.ico')

    try:
        result = subprocess.run(cmd, check=True, capture_output=True, text=True)
        print("✅ Сборка завершена успешно!")
        print(f"📁 Исполняемый файл: CAD-Share.exe")
        return True

    except subprocess.CalledProcessError as e:
        print(f"❌ Ошибка сборки: {e}")
        print(f"Вывод: {e.stdout}")
        print(f"Ошибки: {e.stderr}")
        return False

def cleanup():
    """Очистка временных файлов"""
    print("🗑️ Очистка временных файлов...")

    files_to_remove = [
        'main_embedded.py',
        'CAD-Share.spec',
    ]

    dirs_to_remove = [
        'build',
        '__pycache__',
    ]

    for file in files_to_remove:
        try:
            Path(file).unlink(missing_ok=True)
        except:
            pass

    for dir_name in dirs_to_remove:
        try:
            import shutil
            shutil.rmtree(dir_name, ignore_errors=True)
        except:
            pass

    print("✅ Очистка завершена")

def main():
    """Главная функция сборки"""
    print("🚀 Начинаем сборку CAD-Share.exe\n")

    # Проверяем Python
    print(f"🐍 Python версия: {sys.version}")

    # Скачиваем зависимости
    if not download_dependencies():
        print("❌ Ошибка скачивания зависимостей")
        return False

    # Встраиваем файлы
    if not embed_files_in_source():
        print("❌ Ошибка встраивания файлов")
        return False

    # Собираем exe
    if not build_executable():
        print("❌ Ошибка сборки")
        return False

    # Очищаем временные файлы
    cleanup()

    print("\n🎉 CAD-Share.exe собран успешно!")
    print("📋 Инструкция для проектировщиков:")
    print("   1. Скачать CAD-Share.exe")
    print("   2. Поместить в папку с CAD файлами")
    print("   3. Двойной клик по файлу")
    print("   4. Скопировать ссылку в настройки сайта")

    return True

if __name__ == "__main__":
    success = main()
    if not success:
        input("\n❌ Сборка не удалась. Нажмите Enter...")
        sys.exit(1)
    else:
        input("\n✅ Готово! Нажмите Enter...")