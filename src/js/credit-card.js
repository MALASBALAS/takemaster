//ESTE JS ES UNA COPIA DE INTERNET, NO SE HA USADO EN LA WEB, ESTÁ PENSADO PARA UNA FUNCION VISUAL
//DE UNA TARJETA DE CREDITO, MERAMENTE DECORATIVO.
//TODOS LOS JS USADOS EN LA WEB ESTÁN EN SUS PAGINAS CORRESPONDIENTES MEDIANTE "<SCRIPT>".

document.addEventListener('DOMContentLoaded', () => {
    const tarjeta = document.querySelector('#tarjeta'),
          btnAbrirFormulario = document.querySelector('#btn-abrir-formulario'),
          formulario = document.querySelector('#formulario-tarjeta'),
          numeroTarjeta = document.querySelector('#tarjeta .numero'),
          nombreTarjeta = document.querySelector('#tarjeta .nombre'),
          logoMarca = document.querySelector('#logo-marca'),
          firma = document.querySelector('#tarjeta .firma p'),
          mesExpiracion = document.querySelector('#tarjeta .mes'),
          yearExpiracion = document.querySelector('#tarjeta .year'),
          ccv = document.querySelector('#tarjeta .ccv'),
          btnEnviar = document.querySelector('.btn-enviar'),
          btnEliminar = document.querySelector('.btn-eliminar');

    const mostrarFrente = () => {
        if (tarjeta.classList.contains('active')) {
            tarjeta.classList.remove('active');
        }
    };

    tarjeta.addEventListener('click', () => {
        tarjeta.classList.toggle('active');
    });

    btnAbrirFormulario.addEventListener('click', () => {
        btnAbrirFormulario.classList.toggle('active');
        formulario.classList.toggle('active');
    });

    for (let i = 1; i <= 12; i++) {
        let opcion = document.createElement('option');
        opcion.value = i;
        opcion.innerText = i;
        formulario.selectMes.appendChild(opcion);
    }

    const yearActual = new Date().getFullYear();
    for (let i = yearActual; i <= yearActual + 8; i++) {
        let opcion = document.createElement('option');
        opcion.value = i;
        opcion.innerText = i;
        formulario.selectYear.appendChild(opcion);
    }

    formulario.inputNumero.addEventListener('keyup', (e) => {
        let valorInput = e.target.value;
        formulario.inputNumero.value = valorInput
            .replace(/\s/g, '')
            .replace(/\D/g, '')
            .replace(/([0-9]{4})/g, '$1 ')
            .trim();

        numeroTarjeta.textContent = valorInput;

        if (valorInput == '') {
            numeroTarjeta.textContent = '#### #### #### ####';
            logoMarca.innerHTML = '';
        }

        if (valorInput[0] == 4) {
            logoMarca.innerHTML = '';
            const imagen = document.createElement('img');
            imagen.src = 'img/logos/visa.png';
            logoMarca.appendChild(imagen);
        } else if (valorInput[0] == 5) {
            logoMarca.innerHTML = '';
            const imagen = document.createElement('img');
            imagen.src = 'img/logos/mastercard.png';
            logoMarca.appendChild(imagen);
        }

        mostrarFrente();
    });

    formulario.inputNombre.addEventListener('keyup', (e) => {
        let valorInput = e.target.value;
        formulario.inputNombre.value = valorInput.replace(/[0-9]/g, '');
        nombreTarjeta.textContent = valorInput;
        firma.textContent = valorInput;

        if (valorInput == '') {
            nombreTarjeta.textContent = 'Take Master';
        }

        mostrarFrente();
    });

    formulario.inputCCV.addEventListener('keyup', () => {
        if (!tarjeta.classList.contains('active')) {
            tarjeta.classList.toggle('active');
        }

        formulario.inputCCV.value = formulario.inputCCV.value
            .replace(/\s/g, '')
            .replace(/\D/g, '');

        ccv.textContent = formulario.inputCCV.value;
    });

    btnEnviar.addEventListener('click', (e) => {
        e.preventDefault();

        const cardNumber = formulario.inputNumero.value;
        const cardName = formulario.inputNombre.value;
        const expiryMonth = formulario.selectMes.value;
        const expiryYear = formulario.selectYear.value;
        const ccvValue = formulario.inputCCV.value;

        if (!cardNumber || !cardName || !expiryMonth || !expiryYear || !ccvValue) {
            alert('Todos los campos son obligatorios.');
            return;
        }

        fetch('guardar_datos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                inputNumero: cardNumber,
                inputNombre: cardName,
                selectMes: expiryMonth,
                selectYear: expiryYear,
                inputCCV: ccvValue
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Datos guardados exitosamente.');
                location.reload();
            } else {
                alert('Error al guardar los datos.');
            }
        })
        .catch(error => console.error('Error:', error));
    });

    btnEliminar.addEventListener('click', () => {
        fetch('eliminar_datos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Datos eliminados exitosamente.');
                location.reload();
            } else {
                alert('Error al eliminar los datos.');
            }
        })
        .catch(error => console.error('Error:', error));
    });

    // Cargar datos al inicio
    fetch('obtener_datos.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                formulario.inputNumero.value = data.card_number;
                formulario.inputNombre.value = data.card_name;
                formulario.selectMes.value = data.expiry_month;
                formulario.selectYear.value = data.expiry_year;
                formulario.inputCCV.value = data.ccv;

                numeroTarjeta.textContent = data.card_number;
                nombreTarjeta.textContent = data.card_name;
                mesExpiracion.textContent = data.expiry_month;
                yearExpiracion.textContent = data.expiry_year;
                ccv.textContent = data.ccv;

                if (data.card_number[0] == 4) {
                    const imagen = document.createElement('img');
                    imagen.src = 'img/logos/visa.png';
                    logoMarca.appendChild(imagen);
                } else if (data.card_number[0] == 5) {
                    const imagen = document.createElement('img');
                    imagen.src = 'img/logos/mastercard.png';
                    logoMarca.appendChild(imagen);
                }

                btnEliminar.style.display = 'block';
            } else {
                console.log('No se encontraron datos de la tarjeta');
            }
        })
        .catch(error => console.error('Error:', error));
});
