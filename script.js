// ----------------------------------------
// script.js - JavaScript für Formularvalidierung und Signatur
// ----------------------------------------
document.addEventListener("DOMContentLoaded", function() {
    // Aktuelles Datum als Standardwert setzen
    document.getElementById('date').valueAsDate = new Date();
    
    // Initialisierung des Signature-Pads
    const canvas = document.getElementById('signature-pad');
    const signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'white',
        penColor: 'black'
    });
    
    // Canvas-Größe anpassen
    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);
        signaturePad.clear(); // Signature-Pad zurücksetzen
    }
    
    // Beim Laden und Ändern der Fenstergröße anpassen
    window.onresize = resizeCanvas;
    resizeCanvas();
    
    // Button zum Löschen der Unterschrift
    document.getElementById('clear-signature').addEventListener('click', function() {
        signaturePad.clear();
    });
    
    // Aktivieren des Eingabefelds für benutzerdefinierten Betrag
    const customBeitragRadio = document.getElementById('beitrag_custom');
    const customBeitragValue = document.getElementById('beitrag_custom_value');
    
    customBeitragRadio.addEventListener('change', function() {
        customBeitragValue.disabled = !this.checked;
        if (this.checked) {
            customBeitragValue.focus();
        }
    });
    
    customBeitragValue.addEventListener('click', function() {
        customBeitragRadio.checked = true;
        this.disabled = false;
    });
    
    // Deaktivieren des benutzerdefinierten Betrags bei Start
    customBeitragValue.disabled = !customBeitragRadio.checked;
    
    // Form Validation und Abschicken
    window.validateAndSubmit = function() {
        let isValid = true;
        const form = document.getElementById('membershipForm');
        const requiredFields = form.querySelectorAll('[required]');
        
        // Validation für alle Pflichtfelder
        requiredFields.forEach(field => {
            removeError(field);
            
            if (!field.value.trim()) {
                showError(field, 'Dieses Feld ist erforderlich');
                isValid = false;
            } else if (field.id === 'email' && !isValidEmail(field.value)) {
                showError(field, 'Bitte geben Sie eine gültige E-Mail-Adresse ein');
                isValid = false;
            } else if (field.id === 'iban' && !isValidIBAN(field.value)) {
                showError(field, 'Bitte geben Sie eine gültige IBAN ein');
                isValid = false;
            }
        });
        
        // Überprüfen des benutzerdefinierten Betrags
        if (customBeitragRadio.checked && (!customBeitragValue.value || customBeitragValue.value < 10)) {
            showError(customBeitragValue, 'Bitte geben Sie einen Betrag von mindestens 10€ ein');
            isValid = false;
        }
        
        // Überprüfen der Unterschrift
        if (signaturePad.isEmpty()) {
            showError(canvas, 'Bitte unterschreiben Sie das Formular');
            isValid = false;
        } else {
            // Speichern der Unterschrift im versteckten Feld
            document.getElementById('signature-data').value = signaturePad.toDataURL();
        }
        
        if (isValid) {
            form.submit();
        } else {
            // Zum ersten Fehler scrollen
            const firstError = document.querySelector('.error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    };
    
    // Hilfsfunktionen für Validierung
    function showError(field, message) {
        field.classList.add('error');
        
        // Überprüfen, ob eine Fehlermeldung bereits existiert
        const existingError = field.parentElement.querySelector('.error-message');
        if (!existingError) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.textContent = message;
            field.parentElement.appendChild(errorDiv);
        }
    }
    
    function removeError(field) {
        field.classList.remove('error');
        const errorDiv = field.parentElement.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function isValidIBAN(iban) {
        const ibanRegex = /^DE[0-9]{2}[0-9]{4}[0-9]{4}[0-9]{4}[0-9]{4}[0-9]{2}$/;
        return ibanRegex.test(iban);
    }
});