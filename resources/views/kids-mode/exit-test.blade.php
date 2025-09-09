<!DOCTYPE html>
<html>
<head>
    <title>Test PIN Entry</title>
    <style>
        .pin-digit { 
            width: 50px; 
            height: 50px; 
            border: 1px solid #ccc; 
            display: inline-block; 
            margin: 5px;
            text-align: center;
            line-height: 50px;
        }
        .filled { background: blue; color: white; }
    </style>
</head>
<body>
    <h1>Test PIN Entry</h1>
    
    <div id="pin-display">
        <div class="pin-digit" data-index="0"></div>
        <div class="pin-digit" data-index="1"></div>
        <div class="pin-digit" data-index="2"></div>
        <div class="pin-digit" data-index="3"></div>
    </div>
    
    <div>
        <button data-digit="1">1</button>
        <button data-digit="2">2</button>
        <button data-digit="3">3</button>
        <button data-digit="4">4</button>
    </div>
    
    <div id="debug"></div>

    <script>
        let currentPin = [];
        
        function updateDisplay() {
            const digits = document.querySelectorAll('.pin-digit');
            digits.forEach((digit, index) => {
                if (index < currentPin.length) {
                    digit.textContent = '•';
                    digit.classList.add('filled');
                } else {
                    digit.textContent = '';
                    digit.classList.remove('filled');
                }
            });
            document.getElementById('debug').textContent = 'PIN: ' + currentPin.join('');
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-digit]').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (currentPin.length < 4) {
                        currentPin.push(this.getAttribute('data-digit'));
                        updateDisplay();
                    }
                });
            });
        });
    </script>
</body>
</html>