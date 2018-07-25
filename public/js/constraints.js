function validateRegistrationForm() {

    alerts = document.getElementsByClassName('alert alert-danger col-md-4');
    for(var i = 0; i < alerts.length; i++){
        alerts[i].style.display = "none";
        alerts[i].innerHTML = "";
    }

    var name = document.getElementById('company_name').value;
    var email = document.getElementById('company_email').value;
    var address = document.getElementById('company_address').value;
    var phone = document.getElementById('company_phone').value;
    var contactName = document.getElementById('company_contactName').value;
    var passwordFirst = document.getElementById('company_plainPassword_first').value;
    var passwordSecond = document.getElementById('company_plainPassword_second').value;

    var flag = false;
    if(name == '') {
        flag = true;
        showError('nameError', "Este campo no puede estar vacío. ");
    }
    if(!isValidRegex(name, /[a-zA-Z0-9_-]{3,}/g)) {
        flag = true;
        showError('nameError', "Debe contener al menos 3 caracteres. ");
    }
    if(email == '') {
        flag = true;
        showError('emailError', "Este campo no puede estar vacío. ");
    }
    if(!isValidRegex(email, /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/)) {
        flag = true;
        showError('emailError', "Ingrese un formato válido de email. ");
    }
    if(address == '') {
        flag = true;
        showError('addressError', "Este campo no puede estar vacío. ");
    }
    if(phone == '') {
        flag = true;
        showError('phoneError', "Este campo no puede estar vacío. ");
    }
    if(!isValidRegex(phone, /^\d+$/)) {
        flag = true;
        showError('phoneError', "Este campo solo puede contener números. ");
    }
    if(contactName == '') {
        flag = true;
        showError('contactNameError', "Este campo no puede estar vacío. ");
    }
    if(passwordFirst == '') {
        flag = true;
        showError('passwordFirstError', "Este campo no puede estar vacío. ");
    }
    if(!isValidRegex(passwordFirst, /^.*(?=.{8,})(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[.!#$%&?"]).*$/)) {
        flag = true;
        showError('passwordFirstError', "La contraseña debe contener minimo 8 caracteres, una letra mayúscula, una letra minúscula, y un simbolo (.!#$%&?). ");
    }
    if(passwordSecond == '') {
        flag = true;
        showError('passwordSecondError', "Este campo no puede estar vacío. ");
    }
    if(passwordFirst != passwordSecond) {
        flag = true;
        showError('passwordSecondError', "Las contraseñas no coinciden. ");
    }
    if(!flag) {
        return true;
    }
    
    return false;
}

function isValidRegex(field, expression) {
    var regex = RegExp(expression);
    if(!field.match(expression)) {
        return false;
    }
    if(!regex.test(field)) {
        return false;
    }
    return true;
}

function showError(location, message) {
    divError = document.getElementById(location);
    divError.innerHTML += message;
    divError.style.display = "block";
}