const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');
const scoreElement = document.getElementById('score');
const timerElement = document.getElementById('timer');

// Начальные параметры
const planeY = 50;
let score = 0;
let timeLeft = 120;
let groundOffset = 0;
let bombs = [];
let buildings = [];
let guns = [];

// Параметры игры
const groundSpeed = 2;
const bombSpeed = 5;
const minDistanceBetweenObjects = 150; 

// Создаем начальные объекты
function initializeObjects() {
    // Создаем начальные объекты
    for (let i = 0; i < 10; i++) {
        const obj = createNewObject();
        const x = 400 + i * minDistanceBetweenObjects; 
        obj.x = x;
        
        if (obj.type === 'gun') {
            guns.push(obj);
        } else {
            buildings.push(obj);
        }
    }
}

// Отрисовка самолёта
function drawPlane(x) {
    // Основной корпус
    ctx.fillStyle = '#808080';
    ctx.beginPath();
    ctx.moveTo(x, planeY + 10);
    ctx.lineTo(x + 60, planeY + 10);
    ctx.lineTo(x + 50, planeY + 20);
    ctx.lineTo(x + 10, planeY + 20);
    ctx.closePath();
    ctx.fill();

    // Кабина пилота
    ctx.fillStyle = '#ADD8E6';
    ctx.beginPath();
    ctx.moveTo(x + 45, planeY + 5);
    ctx.lineTo(x + 55, planeY + 5);
    ctx.lineTo(x + 50, planeY + 10);
    ctx.lineTo(x + 40, planeY + 10);
    ctx.closePath();
    ctx.fill();

    // Крыло
    ctx.fillStyle = '#A9A9A9';
    ctx.beginPath();
    ctx.moveTo(x + 20, planeY + 15);
    ctx.lineTo(x + 45, planeY + 15);
    ctx.lineTo(x + 35, planeY + 30);
    ctx.lineTo(x + 15, planeY + 30);
    ctx.closePath();
    ctx.fill();

    // Хвост
    ctx.fillStyle = '#A9A9A9';
    ctx.beginPath();
    ctx.moveTo(x + 5, planeY + 5);
    ctx.lineTo(x + 15, planeY + 5);
    ctx.lineTo(x + 10, planeY + 15);
    ctx.lineTo(x, planeY + 15);
    ctx.closePath();
    ctx.fill();

    // Пропеллер
    ctx.fillStyle = '#000000';
    ctx.beginPath();
    ctx.moveTo(x + 60, planeY + 10);
    ctx.lineTo(x + 65, planeY + 5);
    ctx.lineTo(x + 65, planeY + 15);
    ctx.closePath();
    ctx.fill();
}

// Отрисовка бомбы
function drawBomb(bomb) {
    ctx.fillStyle = 'black';
    ctx.beginPath();
    ctx.arc(bomb.x, bomb.y, 5, 0, Math.PI * 2);
    ctx.fill();
}

// Отрисовка земли
function drawGround() {
    ctx.fillStyle = 'green';
    ctx.fillRect(0, canvas.height - 20, canvas.width, 20);
}

// Отрисовка пушек и зданий
function drawObjects() {
    // Отрисовка пушек
    guns.forEach(gun => {
        const x = gun.x - groundOffset;
        const y = gun.y;
        
        // Основание пушки
        ctx.fillStyle = '#8B4513';
        ctx.fillRect(x, y + 20, gun.width, 10);
        
        // Ствол пушки
        ctx.fillStyle = '#4A4A4A';
        ctx.beginPath();
        ctx.moveTo(x + 15, y + 15);
        ctx.lineTo(x + 30, y + 15);
        ctx.lineTo(x + 28, y);
        ctx.lineTo(x + 17, y);
        ctx.closePath();
        ctx.fill();
        
        // Колеса
        ctx.fillStyle = '#2F4F4F';
        ctx.beginPath();
        ctx.arc(x + 8, y + 28, 5, 0, Math.PI * 2);
        ctx.fill();
        ctx.beginPath();
        ctx.arc(x + 22, y + 28, 5, 0, Math.PI * 2);
        ctx.fill();
        
        // Детали пушки
        ctx.strokeStyle = '#000000';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(x + 22, y + 15);
        ctx.lineTo(x + 22, y + 5);
        ctx.stroke();
    });

    // Отрисовка зданий
    buildings.forEach(building => {
        const x = building.x - groundOffset;
        const y = building.y;
        
        // Основное здание
        ctx.fillStyle = '#CD853F';
        ctx.fillRect(x, y, building.width, building.height);
        
        // Крыша
        ctx.fillStyle = '#8B4513';
        ctx.beginPath();
        ctx.moveTo(x - 5, y);
        ctx.lineTo(x + building.width + 5, y);
        ctx.lineTo(x + building.width/2, y - 20);
        ctx.closePath();
        ctx.fill();
        
        // Окна
        ctx.fillStyle = '#FFD700';
        const windowWidth = 10;
        const windowHeight = 15;
        const windowSpacing = 15;
        
        // Первый ряд окон
        for(let i = 0; i < 3; i++) {
            ctx.fillRect(
                x + 5 + (i * windowSpacing), 
                y + 15, 
                windowWidth, 
                windowHeight
            );
        }
        
        // Второй ряд окон
        for(let i = 0; i < 3; i++) {
            ctx.fillRect(
                x + 5 + (i * windowSpacing), 
                y + 40, 
                windowWidth, 
                windowHeight
            );
        }
        
        // Дверь
        ctx.fillStyle = '#8B4513';
        ctx.fillRect(x + 20, y + 60, 15, 20);
        
        // Дверная ручка
        ctx.fillStyle = '#FFD700';
        ctx.beginPath();
        ctx.arc(x + 31, y + 70, 2, 0, Math.PI * 2);
        ctx.fill();
    });
}

// Проверка столкновений
function checkCollisions() {
    bombs.forEach((bomb, bombIndex) => {
        if (bomb.y >= canvas.height - 20) {
            bombs.splice(bombIndex, 1);
            return;
        }

        guns.forEach((gun, gunIndex) => {
            if (bomb.x >= gun.x - groundOffset && 
                bomb.x <= gun.x - groundOffset + gun.width &&
                bomb.y >= gun.y) {
                score += 1;
                bombs.splice(bombIndex, 1);
                guns.splice(gunIndex, 1);
                scoreElement.textContent = `Очки: ${score}`;
            }
        });

        buildings.forEach((building, buildingIndex) => {
            if (bomb.x >= building.x - groundOffset && 
                bomb.x <= building.x - groundOffset + building.width &&
                bomb.y >= building.y) {
                score -= 1;
                bombs.splice(bombIndex, 1);
                buildings.splice(buildingIndex, 1);
                scoreElement.textContent = `Очки: ${score}`;
            }
        });
    });
}

// Функция для создания нового объекта
function createNewObject() {
    const lastObject = [...guns, ...buildings].reduce((latest, current) => 
        current.x > latest.x ? current : latest, 
        { x: 0 }
    );

    const newX = Math.max(
        lastObject.x + minDistanceBetweenObjects,
        canvas.width + groundOffset
    );

    // Случайный выбор типа объекта
    const isGun = Math.random() < 0.5;

    if (isGun) {
        return {
            type: 'gun',
            x: newX,
            y: canvas.height - 60,
            width: 30,
            height: 30
        };
    } else {
        return {
            type: 'building',
            x: newX,
            y: canvas.height - 100,
            width: 50,
            height: 80
        };
    }
}

// Основной игровой цикл
function gameLoop() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    groundOffset += groundSpeed;
    
    // Удаляем объекты, которые ушли далеко влево
    guns = guns.filter(gun => gun.x - groundOffset > -100);
    buildings = buildings.filter(building => building.x - groundOffset > -100);
    
    // Добавляем новые объекты при необходимости
    const totalObjects = guns.length + buildings.length;
    if (totalObjects < 10) {
        const newObj = createNewObject();
        if (newObj.type === 'gun') {
            guns.push(newObj);
        } else {
            buildings.push(newObj);
        }
    }
    
    drawGround();
    drawPlane(canvas.width / 3);
    drawObjects();
    
    bombs.forEach((bomb, index) => {
        bomb.y += bombSpeed;
        drawBomb(bomb);
    });
    
    checkCollisions();
}

// Обработчик нажатия пробела
function handleKeyPress(e) {
    if (e.code === 'Space') {
        bombs.push({
            x: canvas.width / 3 + 30,
            y: planeY + 25
        });
    }
}

// Функция сброса игры
function resetGame() {
    score = 0;
    timeLeft = 120;
    groundOffset = 0;
    bombs = [];
    buildings = [];
    guns = [];
    
    scoreElement.textContent = `Очки: ${score}`;
    timerElement.textContent = `Время: ${timeLeft}`;
    
    initializeObjects();
}

// Обновляем функцию таймера
function updateTimer() {
    timeLeft--;
    timerElement.textContent = `Время: ${timeLeft}`;
    
    if (timeLeft <= 0) {
        alert(`Игра окончена! Ваш счёт: ${score}`);
        resetGame(); // Вместо перезагрузки страницы вызываем функцию сброса
    }
}

// Инициализация игры
initializeObjects();
document.addEventListener('keydown', handleKeyPress);
setInterval(gameLoop, 1000 / 60);
setInterval(updateTimer, 1000); 