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
    
    // IBAN-Formatierung für bessere Lesbarkeit
    const ibanField = document.getElementById('iban');
    ibanField.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s+/g, ''); // Alle Leerzeichen entfernen
        
        // Nur Buchstaben und Ziffern erlauben
        value = value.replace(/[^A-Z0-9]/gi, '');
        
        // In Großbuchstaben umwandeln
        value = value.toUpperCase();
        
        // Formatierung hinzufügen (4er Gruppen)
        let formattedValue = '';
        for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) {
                formattedValue += ' ';
            }
            formattedValue += value[i];
        }
        
        e.target.value = formattedValue;
    });
    
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
                showError(field, 'Bitte geben Sie eine gültige IBAN ein (Format: DE + 20 Ziffern)');
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
        // Leerzeichen entfernen und Format prüfen
        const cleanedIBAN = iban.replace(/\s+/g, '');
        // Deutsche IBAN: DE + 20 Ziffern = 22 Zeichen
        const ibanRegex = /^DE[0-9]{20}$/;
        return ibanRegex.test(cleanedIBAN);
    }
    
    // Nach Seitenladen auf Fehlermeldungen aus der Session prüfen
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error')) {
        const errorContainer = document.getElementById('error-container');
        const errorMessage = document.getElementById('error-message');
        
        errorContainer.style.display = 'block';
        errorMessage.innerHTML = decodeURIComponent(urlParams.get('error'));
        
        // Zum Fehlermeldungscontainer scrollen
        errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});