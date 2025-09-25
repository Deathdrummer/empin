#!/usr/bin/env python3
"""
CAD-Share - Монолитное приложение для расшаривания CAD файлов
Автор: AI Assistant
Версия: 1.0
"""

import tkinter as tk
from tkinter import ttk, scrolledtext, messagebox
import subprocess
import threading
import os
import sys
import time
import re
import socket
import tempfile
import zipfile
import base64
from pathlib import Path

# Встроенные файлы (будут добавлены при компиляции)
MONGOOSE_DATA = ""  # Здесь будет base64 mongoose.exe
PLINK_DATA = ""     # Здесь будет base64 plink.exe для SSH

class CADShareApp:
    def __init__(self, root):
        self.root = root
        self.root.title("CAD-Share v1.0 - Расшаривание CAD файлов")
        self.root.geometry("600x500")
        self.root.resizable(False, False)

        # Переменные состояния
        self.mongoose_process = None
        self.ssh_process = None
        self.public_url = None
        self.temp_dir = None
        self.is_running = False

        self.setup_ui()
        self.setup_temp_files()

    def setup_ui(self):
        """Создание интерфейса"""
        # Заголовок
        header_frame = tk.Frame(self.root, bg="#2c3e50", height=80)
        header_frame.pack(fill="x", padx=10, pady=5)
        header_frame.pack_propagate(False)

        title_label = tk.Label(
            header_frame,
            text="CAD-Share",
            font=("Arial", 20, "bold"),
            fg="white",
            bg="#2c3e50"
        )
        title_label.pack(pady=20)

        # Информационная панель
        info_frame = tk.LabelFrame(self.root, text="Информация", padx=10, pady=10)
        info_frame.pack(fill="x", padx=10, pady=5)

        self.status_label = tk.Label(
            info_frame,
            text="🔴 Сервер остановлен",
            font=("Arial", 12),
            fg="red"
        )
        self.status_label.pack(anchor="w")

        self.folder_label = tk.Label(
            info_frame,
            text=f"📁 Папка: {os.getcwd()}",
            font=("Arial", 10)
        )
        self.folder_label.pack(anchor="w", pady=(5, 0))

        # Панель управления
        control_frame = tk.LabelFrame(self.root, text="Управление", padx=10, pady=10)
        control_frame.pack(fill="x", padx=10, pady=5)

        self.start_button = tk.Button(
            control_frame,
            text="🚀 ЗАПУСТИТЬ СЕРВЕР",
            command=self.start_server,
            bg="#27ae60",
            fg="white",
            font=("Arial", 12, "bold"),
            height=2
        )
        self.start_button.pack(fill="x", pady=5)

        self.stop_button = tk.Button(
            control_frame,
            text="⏹️ ОСТАНОВИТЬ СЕРВЕР",
            command=self.stop_server,
            bg="#e74c3c",
            fg="white",
            font=("Arial", 12, "bold"),
            height=2,
            state="disabled"
        )
        self.stop_button.pack(fill="x", pady=5)

        # Панель с публичной ссылкой
        url_frame = tk.LabelFrame(self.root, text="Публичная ссылка для настроек сайта", padx=10, pady=10)
        url_frame.pack(fill="x", padx=10, pady=5)

        self.url_entry = tk.Entry(
            url_frame,
            font=("Arial", 12),
            state="readonly",
            bg="#ecf0f1"
        )
        self.url_entry.pack(fill="x", pady=5)

        self.copy_button = tk.Button(
            url_frame,
            text="📋 СКОПИРОВАТЬ ССЫЛКУ",
            command=self.copy_url,
            bg="#3498db",
            fg="white",
            font=("Arial", 11, "bold"),
            state="disabled"
        )
        self.copy_button.pack(fill="x", pady=5)

        # Лог
        log_frame = tk.LabelFrame(self.root, text="Журнал событий", padx=10, pady=10)
        log_frame.pack(fill="both", expand=True, padx=10, pady=5)

        self.log_text = scrolledtext.ScrolledText(
            log_frame,
            height=8,
            font=("Consolas", 9),
            bg="#2c3e50",
            fg="#ecf0f1",
            state="disabled"
        )
        self.log_text.pack(fill="both", expand=True)

        # Обработчик закрытия
        self.root.protocol("WM_DELETE_WINDOW", self.on_closing)

        self.log("✅ CAD-Share готов к работе!")
        self.log(f"📁 Рабочая папка: {os.getcwd()}")

    def setup_temp_files(self):
        """Создание временных файлов из встроенных данных"""
        try:
            self.temp_dir = tempfile.mkdtemp(prefix="cad_share_")
            self.log(f"📁 Создана временная папка: {self.temp_dir}")

            # TODO: Распаковать mongoose.exe и plink.exe из base64
            # Пока используем заглушки
            self.mongoose_path = os.path.join(self.temp_dir, "mongoose.exe")
            self.plink_path = os.path.join(self.temp_dir, "plink.exe")

            # Создаем пустые файлы для тестирования
            with open(self.mongoose_path, 'w') as f:
                f.write("# mongoose.exe placeholder")
            with open(self.plink_path, 'w') as f:
                f.write("# plink.exe placeholder")

        except Exception as e:
            self.log(f"❌ Ошибка создания временных файлов: {str(e)}")

    def log(self, message):
        """Добавление сообщения в лог"""
        timestamp = time.strftime("%H:%M:%S")
        formatted_message = f"[{timestamp}] {message}\n"

        self.log_text.config(state="normal")
        self.log_text.insert(tk.END, formatted_message)
        self.log_text.see(tk.END)
        self.log_text.config(state="disabled")

        # Также выводим в консоль
        print(formatted_message.strip())

    def find_free_port(self, start_port=8000):
        """Поиск свободного порта"""
        for port in range(start_port, start_port + 100):
            try:
                sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
                sock.bind(('localhost', port))
                sock.close()
                return port
            except:
                continue
        return None

    def start_mongoose_server(self, port):
        """Запуск Mongoose веб-сервера"""
        try:
            # Команда для запуска mongoose
            cmd = [self.mongoose_path, f"-p", str(port)]

            self.log(f"🚀 Запускаем Mongoose на порту {port}...")

            # Для тестирования используем простой HTTP сервер Python
            cmd = [sys.executable, "-m", "http.server", str(port)]

            self.mongoose_process = subprocess.Popen(
                cmd,
                cwd=os.getcwd(),
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                creationflags=subprocess.CREATE_NO_WINDOW if os.name == 'nt' else 0
            )

            time.sleep(2)  # Даем серверу время запуститься

            if self.mongoose_process.poll() is None:
                self.log(f"✅ Веб-сервер запущен на http://localhost:{port}")
                return True
            else:
                error = self.mongoose_process.stderr.read().decode()
                self.log(f"❌ Ошибка запуска веб-сервера: {error}")
                return False

        except Exception as e:
            self.log(f"❌ Ошибка запуска Mongoose: {str(e)}")
            return False

    def create_ssh_tunnel(self, port):
        """Создание SSH туннеля через localhost.run"""
        try:
            self.log("🌐 Создаем SSH туннель через localhost.run...")

            # Команда SSH для создания туннеля
            cmd = [
                "ssh",
                "-R", f"80:localhost:{port}",
                "-o", "StrictHostKeyChecking=no",
                "-o", "UserKnownHostsFile=/dev/null",
                "-o", "LogLevel=ERROR",
                "nokey@localhost.run"
            ]

            self.ssh_process = subprocess.Popen(
                cmd,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                stdin=subprocess.PIPE,
                text=True,
                creationflags=subprocess.CREATE_NO_WINDOW if os.name == 'nt' else 0
            )

            # Ждем получения URL
            self.log("⏳ Ожидаем получения публичного URL...")

            # Читаем вывод SSH в отдельном потоке
            threading.Thread(target=self.read_ssh_output, daemon=True).start()

            return True

        except Exception as e:
            self.log(f"❌ Ошибка создания SSH туннеля: {str(e)}")
            return False

    def read_ssh_output(self):
        """Чтение вывода SSH для получения URL"""
        try:
            while self.ssh_process and self.ssh_process.poll() is None:
                line = self.ssh_process.stderr.readline()
                if line:
                    self.log(f"SSH: {line.strip()}")

                    # Ищем URL в выводе
                    url_match = re.search(r'https://[a-zA-Z0-9\-]+\.localhost\.run', line)
                    if url_match:
                        self.public_url = url_match.group(0)
                        self.root.after(0, self.on_url_received)
                        break

        except Exception as e:
            self.log(f"❌ Ошибка чтения SSH: {str(e)}")

    def on_url_received(self):
        """Обработчик получения публичного URL"""
        if self.public_url:
            self.log(f"🎉 Публичный URL получен: {self.public_url}")

            self.url_entry.config(state="normal")
            self.url_entry.delete(0, tk.END)
            self.url_entry.insert(0, self.public_url)
            self.url_entry.config(state="readonly")

            self.copy_button.config(state="normal")

            # Обновляем статус
            self.status_label.config(text="🟢 Сервер работает", fg="green")

    def start_server(self):
        """Запуск сервера и туннеля"""
        if self.is_running:
            return

        try:
            # Ищем свободный порт
            port = self.find_free_port()
            if not port:
                messagebox.showerror("Ошибка", "Не удалось найти свободный порт")
                return

            self.log(f"🔍 Найден свободный порт: {port}")

            # Запускаем веб-сервер
            if not self.start_mongoose_server(port):
                return

            # Создаем SSH туннель
            if not self.create_ssh_tunnel(port):
                self.stop_server()
                return

            # Обновляем интерфейс
            self.is_running = True
            self.start_button.config(state="disabled")
            self.stop_button.config(state="normal")

            self.log("✅ Сервер успешно запущен!")
            self.log("💡 Скопируйте публичную ссылку в настройки сайта")

        except Exception as e:
            self.log(f"❌ Ошибка запуска: {str(e)}")
            messagebox.showerror("Ошибка", f"Не удалось запустить сервер:\n{str(e)}")

    def stop_server(self):
        """Остановка сервера и туннеля"""
        self.log("⏹️ Останавливаем сервер...")

        # Останавливаем процессы
        if self.mongoose_process:
            self.mongoose_process.terminate()
            self.mongoose_process = None
            self.log("✅ Веб-сервер остановлен")

        if self.ssh_process:
            self.ssh_process.terminate()
            self.ssh_process = None
            self.log("✅ SSH туннель закрыт")

        # Очищаем интерфейс
        self.public_url = None
        self.url_entry.config(state="normal")
        self.url_entry.delete(0, tk.END)
        self.url_entry.config(state="readonly")

        self.copy_button.config(state="disabled")
        self.status_label.config(text="🔴 Сервер остановлен", fg="red")

        # Обновляем кнопки
        self.is_running = False
        self.start_button.config(state="normal")
        self.stop_button.config(state="disabled")

        self.log("✅ Сервер полностью остановлен")

    def copy_url(self):
        """Копирование URL в буфер обмена"""
        if self.public_url:
            self.root.clipboard_clear()
            self.root.clipboard_append(self.public_url)
            self.log("📋 URL скопирован в буфер обмена!")
            messagebox.showinfo("Готово", "Ссылка скопирована в буфер обмена!")

    def on_closing(self):
        """Обработчик закрытия приложения"""
        if self.is_running:
            if messagebox.askokcancel("Выход", "Сервер еще работает. Остановить и выйти?"):
                self.stop_server()
                time.sleep(1)
                self.cleanup()
                self.root.destroy()
        else:
            self.cleanup()
            self.root.destroy()

    def cleanup(self):
        """Очистка временных файлов"""
        try:
            if self.temp_dir and os.path.exists(self.temp_dir):
                import shutil
                shutil.rmtree(self.temp_dir)
                self.log("🗑️ Временные файлы удалены")
        except Exception as e:
            self.log(f"⚠️ Ошибка очистки: {str(e)}")

def main():
    """Главная функция"""
    try:
        root = tk.Tk()
        app = CADShareApp(root)
        root.mainloop()
    except Exception as e:
        print(f"Критическая ошибка: {str(e)}")
        input("Нажмите Enter для выхода...")

if __name__ == "__main__":
    main()