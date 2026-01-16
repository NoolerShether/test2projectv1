// Скролл эффект для хедера
window.addEventListener('scroll', () => {
    const header = document.getElementById('header');
    if (window.scrollY > 50) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }
});

// Модальные окна
function openModal(type) {
    const modals = {
        'login': document.getElementById('loginModal'),
        'register': document.getElementById('registerModal'),
        'forgot': document.getElementById('forgotModal')
    };
    
    if (modals[type]) {
        modals[type].classList.add('active');
    }
}

function closeModal(type) {
    const modals = {
        'login': document.getElementById('loginModal'),
        'register': document.getElementById('registerModal'),
        'forgot': document.getElementById('forgotModal')
    };
    
    if (modals[type]) {
        modals[type].classList.remove('active');
    }
}

// Закрытие модального окна по клику вне его
window.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});

// Обработка формы входа
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(loginForm);
        const messageDiv = document.getElementById('loginMessage');
        
        try {
            const response = await fetch('ajax/login.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            showMessage(messageDiv, data.message, data.success);
            
            if (data.success) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);
            }
        } catch (error) {
            showMessage(messageDiv, 'Ошибка подключения к серверу', false);
        }
    });
}

// Обработка формы регистрации
const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(registerForm);
        const messageDiv = document.getElementById('registerMessage');
        
        // Проверка совпадения паролей
        const password = formData.get('password');
        const passwordConfirm = formData.get('password_confirm');
        
        if (password !== passwordConfirm) {
            showMessage(messageDiv, 'Пароли не совпадают', false);
            return;
        }
        
        try {
            const response = await fetch('ajax/register.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            showMessage(messageDiv, data.message, data.success);
            
            if (data.success) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            }
        } catch (error) {
            showMessage(messageDiv, 'Ошибка подключения к серверу', false);
        }
    });
}

// Обработка формы восстановления пароля
const forgotForm = document.getElementById('forgotForm');
if (forgotForm) {
    forgotForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(forgotForm);
        const messageDiv = document.getElementById('forgotMessage');
        
        // Показываем загрузку
        showMessage(messageDiv, '⏳ Отправка запроса...', true);
        
        try {
            const response = await fetch('ajax/forgot-password.php', {
                method: 'POST',
                body: formData
            });
            
            // Проверка статуса ответа
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            // Если есть новый пароль - показываем его с кнопкой копирования
            if (data.new_password) {
                const passwordHtml = `
                    <div style="margin-top: 15px; padding: 20px; background: #f8f9fa; border: 2px solid #00C853; border-radius: 10px;">
                        <strong style="font-size: 18px; color: #00C853;">✅ Ваш новый пароль:</strong><br><br>
                        <div style="background: white; padding: 15px; border-radius: 5px; font-family: 'Courier New', monospace; font-size: 24px; font-weight: bold; letter-spacing: 2px; color: #333; text-align: center;">
                            ${data.new_password}
                        </div>
                        <button onclick="copyPassword('${data.new_password}')" style="margin-top: 15px; width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600;">
                            📋 Скопировать пароль
                        </button>
                        <p style="margin-top: 15px; color: #856404; font-size: 14px;">
                            ${data.note || 'Сохраните этот пароль в надежном месте!'}
                        </p>
                    </div>
                `;
                showMessage(messageDiv, data.message + passwordHtml, data.success);
            } else if (data.reset_link) {
                const linkHtml = `
                    <div style="margin-top: 15px;">
                        <a href="${data.reset_link}" 
                           style="color: #007bff; text-decoration: underline; word-break: break-all;"
                           target="_blank">
                            ${data.reset_link}
                        </a>
                    </div>
                `;
                showMessage(messageDiv, data.message + linkHtml, data.success);
            } else {
                showMessage(messageDiv, data.message, data.success);
            }
            
            // Если есть детали ошибки - вывести в консоль
            if (data.error_details) {
                console.error('Error details:', data.error_details);
            }
            
            if (data.success && !data.new_password) {
                setTimeout(() => {
                    if (!data.reset_link) {
                        forgotForm.reset();
                    }
                }, 2000);
            }
        } catch (error) {
            console.error('Fetch error:', error);
            showMessage(messageDiv, 'Ошибка подключения к серверу: ' + error.message, false);
        }
    });
}

// Функция копирования пароля
window.copyPassword = function(password) {
    navigator.clipboard.writeText(password).then(() => {
        alert('✅ Пароль скопирован в буфер обмена!');
    }).catch(err => {
        // Fallback для старых браузеров
        const textArea = document.createElement('textarea');
        textArea.value = password;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            alert('✅ Пароль скопирован!');
        } catch (err) {
            alert('❌ Не удалось скопировать. Скопируйте вручную: ' + password);
        }
        document.body.removeChild(textArea);
    });
};

// Функция отображения сообщений
function showMessage(element, message, isSuccess) {
    element.innerHTML = message; // Изменено с textContent на innerHTML для ссылок
    element.className = 'message ' + (isSuccess ? 'success' : 'error');
    element.style.display = 'block';
    
    // Не скрываем автоматически если есть ссылка
    if (!message.includes('<a href')) {
        setTimeout(() => {
            element.style.display = 'none';
        }, 5000);
    }
}

// Плавная прокрутка к якорям
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#' && !href.includes('onclick')) {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        }
    });
});

// Анимация появления элементов при скролле
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -100px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Применяем анимации ко всем карточкам
document.querySelectorAll('.news-card, .shop-item, .package-card, .stat-card, .finance-card').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(30px)';
    el.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
    observer.observe(el);
});

// Добавляем задержку для последовательного появления
document.querySelectorAll('.news-card').forEach((el, index) => {
    el.style.transitionDelay = `${index * 0.1}s`;
});

document.querySelectorAll('.package-card').forEach((el, index) => {
    el.style.transitionDelay = `${index * 0.08}s`;
});

document.querySelectorAll('.shop-item').forEach((el, index) => {
    el.style.transitionDelay = `${index * 0.1}s`;
});

// Загрузка новостей
async function loadNews() {
    const container = document.getElementById('newsContainer');
    if (!container) return;
    
    try {
        const response = await fetch('ajax/get-news.php');
        const data = await response.json();
        
        if (data.success && data.news) {
            container.innerHTML = data.news.map(item => `
                <div class="news-card">
                    ${item.image_html}
                    <div class="news-content">
                        <h3>${item.title}</h3>
                        <p>${item.description}</p>
                        <div class="news-footer">
                            <span class="news-date">${item.date}</span>
                            <a href="#" class="news-link">Подробнее →</a>
                        </div>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Ошибка загрузки новостей:', error);
    }
}

// Загрузка пакетов
async function loadPackages() {
    const container = document.getElementById('packagesContainer');
    if (!container) return;
    
    try {
        const response = await fetch('ajax/get-packages.php');
        const data = await response.json();
        
        if (data.success && data.packages) {
            container.innerHTML = data.packages.map(pkg => `
                <div class="package-card">
                    <div class="package-title">НАБОР</div>
                    <div class="package-name">${pkg.title}</div>
                    ${pkg.image_html}
                    <div class="package-price">${pkg.formatted_amount}</div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Ошибка загрузки пакетов:', error);
    }
}

// Загрузка данных при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    loadNews();
    loadPackages();
});
