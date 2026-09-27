document.addEventListener('DOMContentLoaded', () => {
    
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            let isValid = true;
            const username = document.getElementById('username');
            const password = document.getElementById('password');

            document.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));
            document.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');

            if (!username.value.trim()) {
                username.classList.add('is-invalid');
                username.nextElementSibling.style.display = 'block';
                isValid = false;
            }

            if (!password.value.trim()) {
                password.classList.add('is-invalid');
                password.nextElementSibling.style.display = 'block';
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    const canvas = document.getElementById('particles-canvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let width = canvas.width = window.innerWidth;
        let height = canvas.height = window.innerHeight;

        const particles = [];
        const mouse = { x: width / 2, y: height / 2 };
        const colors = ['#e30613', '#ff1e2c', '#000000', '#1a1a1a'];

        window.addEventListener('resize', () => {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        });

        window.addEventListener('mousemove', (e) => {
            mouse.x = e.clientX;
            mouse.y = e.clientY;
        });

        class Particle {
            constructor() {
                this.x = Math.random() * width;
                this.y = Math.random() * height;
                this.size = Math.random() * 3 + 1;
                this.color = colors[Math.floor(Math.random() * colors.length)];
                this.speedX = Math.random() * 2 - 1;
                this.speedY = Math.random() * 2 - 1;
                this.vx = 0;
                this.vy = 0;
            }

            update() {
                this.x += this.speedX + this.vx;
                this.y += this.speedY + this.vy;

                const dx = mouse.x - this.x;
                const dy = mouse.y - this.y;
                const distance = Math.sqrt(dx * dx + dy * dy);

                if (distance < 150) {
                    this.vx += dx * 0.0005;
                    this.vy += dy * 0.0005;
                } else {
                    this.vx *= 0.95;
                    this.vy *= 0.95;
                }

                if (this.x < 0 || this.x > width) this.speedX *= -1;
                if (this.y < 0 || this.y > height) this.speedY *= -1;

                if (this.x < -50) this.x = width + 50;
                if (this.x > width + 50) this.x = -50;
                if (this.y < -50) this.y = height + 50;
                if (this.y > height + 50) this.y = -50;
            }

            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fillStyle = this.color;
                ctx.fill();
            }
        }

        for (let i = 0; i < 80; i++) {
            particles.push(new Particle());
        }

        function animate() {
            ctx.clearRect(0, 0, width, height);
            for (let i = 0; i < particles.length; i++) {
                particles[i].update();
                particles[i].draw();
            }
            requestAnimationFrame(animate);
        }

        animate();
    }
});
