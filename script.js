let currentSlide = 0;
let slideInterval;
let currentEvent = '';

document.addEventListener('DOMContentLoaded', function() {
    initSlider();
    initNavigation();
    initModals();
    initRegistrationForm();
    initScrollToTop();
    
    const headerRegisterBtn = document.getElementById('headerRegisterBtn');
    const mobileRegisterBtn = document.getElementById('mobileRegisterBtn');
    const sliderRegisterBtn = document.getElementById('sliderRegisterBtn');
    
    if (headerRegisterBtn) {
        headerRegisterBtn.addEventListener('click', function() {
            openRegistrationModal();
        });
    }
    
    if (mobileRegisterBtn) {
        mobileRegisterBtn.addEventListener('click', function() {
            openRegistrationModal();
            const nav = document.getElementById('nav');
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            nav.classList.remove('mobile-open');
            mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
        });
    }
    
    if (sliderRegisterBtn) {
        sliderRegisterBtn.addEventListener('click', function() {
            openRegistrationModal();
        });
    }
});

function openRegistrationModal() {
    const modal = document.getElementById('registrationModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        document.getElementById('selectedEvent').textContent = 'Выберите мероприятие из списка ниже';
        document.getElementById('eventDate').value = '';
    }
}

function initSlider() {
    const slides = document.querySelectorAll('.slide');
    const indicators = document.querySelectorAll('.indicator');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const sliderContainer = document.querySelector('.slider-container');
    
    function showSlide(index) {
        if (index >= slides.length) index = 0;
        if (index < 0) index = slides.length - 1;
        
        slides.forEach(slide => slide.classList.remove('active'));
        indicators.forEach(indicator => indicator.classList.remove('active'));
        
        slides[index].classList.add('active');
        indicators[index].classList.add('active');
        currentSlide = index;
    }
    
    function nextSlide() {
        showSlide(currentSlide + 1);
    }
    
    function prevSlide() {
        showSlide(currentSlide - 1);
    }
    
    function startAutoSlide() {
        slideInterval = setInterval(nextSlide, 5000);
    }
    
    if (sliderContainer) {
        sliderContainer.addEventListener('mouseenter', () => {
            clearInterval(slideInterval);
        });
        
        sliderContainer.addEventListener('mouseleave', () => {
            startAutoSlide();
        });
    }
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            prevSlide();
            clearInterval(slideInterval);
            startAutoSlide();
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            nextSlide();
            clearInterval(slideInterval);
            startAutoSlide();
        });
    }
    
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => {
            showSlide(index);
            clearInterval(slideInterval);
            startAutoSlide();
        });
    });
    
    startAutoSlide();
}

function initNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const nav = document.getElementById('nav');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                navLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                window.scrollTo({
                    top: targetElement.offsetTop - 70,
                    behavior: 'smooth'
                });
                
                if (nav.classList.contains('mobile-open')) {
                    nav.classList.remove('mobile-open');
                    mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
                }
            }
        });
    });
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            nav.classList.toggle('mobile-open');
            this.innerHTML = nav.classList.contains('mobile-open') 
                ? '<i class="fas fa-times"></i>' 
                : '<i class="fas fa-bars"></i>';
                
            const mobileRegisterBtn = document.getElementById('mobileRegisterBtn');
            if (mobileRegisterBtn) {
                if (nav.classList.contains('mobile-open')) {
                    mobileRegisterBtn.style.display = 'block';
                } else {
                    mobileRegisterBtn.style.display = 'none';
                }
            }
        });
    }
    
    window.addEventListener('scroll', function() {
        const scrollPosition = window.scrollY + 100;
        
        const sections = document.querySelectorAll('section');
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            const sectionId = section.getAttribute('id');
            
            if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === `#${sectionId}`) {
                        link.classList.add('active');
                    }
                });
            }
        });
    });
}

function initModals() {
    const detailButtons = document.querySelectorAll('.btn-details');
    const registerButtons = document.querySelectorAll('.btn-register');
    const closeButtons = document.querySelectorAll('.modal-close');
    
    detailButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal') + 'Modal';
            const modal = document.getElementById(modalId);
            
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        });
    });
    
    registerButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = document.getElementById('registrationModal');
            currentEvent = this.getAttribute('data-event');
            
            const eventTitles = {
                'festival': 'Фестиваль "Семейные традиции"',
                'seminar': 'Семинар "Современная семья"',
                'exhibition': 'Выставка "Семейные ценности"',
                'marathon': 'Онлайн-марафон "Родительство"'
            };
            
            if (modal) {
                document.getElementById('selectedEvent').textContent = 
                    `Выбрано мероприятие: ${eventTitles[currentEvent] || 'Мероприятие'}`;
                
                const eventDateSelect = document.getElementById('eventDate');
                for (let i = 0; i < eventDateSelect.options.length; i++) {
                    if (eventDateSelect.options[i].text.includes(eventTitles[currentEvent])) {
                        eventDateSelect.selectedIndex = i;
                        break;
                    }
                }
                
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        });
    });
    
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    });
    
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            modals.forEach(modal => {
                if (modal.style.display === 'flex') {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
        }
    });
}

function initRegistrationForm() {
    const form = document.getElementById('registrationForm');
    const clearBtn = document.getElementById('clearFormBtn');
    
    if (!form) return;
    
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            form.reset();
            
            const errorMessages = document.querySelectorAll('.error-message');
            errorMessages.forEach(error => {
                error.textContent = '';
                error.style.display = 'none';
            });
            
            const errorInputs = document.querySelectorAll('.error');
            errorInputs.forEach(input => {
                input.classList.remove('error');
            });
            
            document.getElementById('selectedEvent').textContent = 'Выберите мероприятие из списка ниже';
        });
    }
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        let isValid = true;
        
        // Валидация ФИО
        const fullNameInput = document.getElementById('fullName');
        const fullNameError = document.getElementById('fullNameError');
        if (!fullNameInput.value.trim()) {
            showError(fullNameInput, fullNameError, 'Пожалуйста, введите ФИО');
            isValid = false;
        } else if (fullNameInput.value.trim().split(' ').length < 2) {
            showError(fullNameInput, fullNameError, 'Введите фамилию, имя и отчество');
            isValid = false;
        } else {
            hideError(fullNameInput, fullNameError);
        }
        
        // Валидация email
        const emailInput = document.getElementById('email');
        const emailError = document.getElementById('emailError');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailInput.value.trim() || !emailRegex.test(emailInput.value)) {
            showError(emailInput, emailError, 'Пожалуйста, введите корректный email');
            isValid = false;
        } else {
            hideError(emailInput, emailError);
        }
        
        // Валидация телефона
        const phoneInput = document.getElementById('phone');
        const phoneError = document.getElementById('phoneError');
        const phoneRegex = /^(\+7|8)[\s\-]?\(?\d{3}\)?[\s\-]?\d{3}[\s\-]?\d{2}[\s\-]?\d{2}$/;
        if (!phoneInput.value.trim() || !phoneRegex.test(phoneInput.value.replace(/\s/g, ''))) {
            showError(phoneInput, phoneError, 'Пожалуйста, введите корректный номер телефона (+7 XXX XXX-XX-XX)');
            isValid = false;
        } else {
            hideError(phoneInput, phoneError);
        }
        
        // Валидация формы участия
        const participationInput = document.getElementById('participation');
        const participationError = document.getElementById('participationError');
        if (!participationInput.value) {
            showError(participationInput, participationError, 'Пожалуйста, выберите форму участия');
            isValid = false;
        } else {
            hideError(participationInput, participationError);
        }
        
        // Валидация даты мероприятия
        const dateInput = document.getElementById('eventDate');
        const dateError = document.getElementById('eventDateError');
        if (!dateInput.value) {
            showError(dateInput, dateError, 'Пожалуйста, выберите дату мероприятия');
            isValid = false;
        } else {
            hideError(dateInput, dateError);
        }
        
        if (!isValid) {
            return;
        }
        
        // Сбор данных формы
        const formData = {
            fullName: fullNameInput.value.trim(),
            email: emailInput.value.trim(),
            phone: phoneInput.value.trim(),
            eventDate: dateInput.value,
            participation: participationInput.value,
            participants: document.getElementById('participants').value,
            comments: document.getElementById('comments').value.trim()
        };
        
        // Отправка данных на сервер
        try {
            // Показываем индикатор загрузки
            const submitBtn = form.querySelector('.btn-submit');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
            submitBtn.disabled = true;
            
            // Отправляем запрос на сервер
            const response = await fetch('process_registration.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Успешная регистрация
                alert('✅ ' + result.message);
                
                // Закрываем модальное окно
                const modal = document.getElementById('registrationModal');
                if (modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
                
                // Сбрасываем форму
                form.reset();
                document.getElementById('selectedEvent').textContent = 'Выберите мероприятие из списка ниже';
                
                // Сбрасываем ошибки
                const errorMessages = document.querySelectorAll('.error-message');
                errorMessages.forEach(error => {
                    error.textContent = '';
                    error.style.display = 'none';
                });
                
                const errorInputs = document.querySelectorAll('.error');
                errorInputs.forEach(input => {
                    input.classList.remove('error');
                });
            } else {
                // Показываем ошибки сервера
                if (result.errors && result.errors.length > 0) {
                    // Если сервер вернул массив ошибок
                    alert('❌ ' + result.errors.join('\n'));
                } else if (result.message) {
                    // Если сервер вернул одно сообщение об ошибке
                    alert('❌ ' + result.message);
                } else {
                    alert('❌ Произошла ошибка при регистрации');
                }
            }
        } catch (error) {
            console.error('Ошибка сети:', error);
            alert('❌ Ошибка соединения с сервером. Проверьте подключение к интернету.');
        } finally {
            // Восстанавливаем кнопку отправки
            const submitBtn = form.querySelector('.btn-submit');
            submitBtn.innerHTML = originalText || '<i class="fas fa-check"></i> Зарегистрироваться';
            submitBtn.disabled = false;
        }
    });
    
    function showError(input, errorElement, message) {
        input.classList.add('error');
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        
        // Прокручиваем к первой ошибке
        if (isValid) {
            input.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    function hideError(input, errorElement) {
        input.classList.remove('error');
        errorElement.textContent = '';
        errorElement.style.display = 'none';
    }
}

function initScrollToTop() {
    const toTopBtn = document.getElementById('toTopBtn');
    const footerToTopBtn = document.getElementById('footerToTopBtn');
    
    if (toTopBtn) {
        toTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    if (footerToTopBtn) {
        footerToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    window.addEventListener('scroll', function() {
        if (toTopBtn) {
            if (window.scrollY > 500) {
                toTopBtn.style.display = 'block';
            } else {
                toTopBtn.style.display = 'none';
            }
        }
    });
}