import Swal from 'sweetalert2';

window.Swal = Swal;

// Toast reutilizable (esquina superior derecha)
window.toast = (options = {}) => {
    return Swal.fire({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        ...options,
    });
};

// Confirmación de eliminación reutilizable -> devuelve una promesa
window.confirmarEliminar = (mensaje = '¿Deseas eliminar este registro?') => {
    return Swal.fire({
        title: '¿Estás seguro?',
        text: mensaje,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
    });
};

// Carga diferida de Tesseract.js (OCR) solo cuando se necesita
window.cargarTesseract = () =>
    new Promise((resolve, reject) => {
        if (window.Tesseract) {
            return resolve(window.Tesseract);
        }
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js';
        s.onload = () => resolve(window.Tesseract);
        s.onerror = () => reject(new Error('No se pudo cargar el OCR'));
        document.head.appendChild(s);
    });

// Preprocesa una imagen para mejorar el OCR: escala de grises, aumento de
// contraste (binarización suave) y escalado a ~1600px de ancho.
window.preprocesarImagen = (file) =>
    new Promise((resolve) => {
        const img = new Image();
        const url = URL.createObjectURL(file);
        img.onload = () => {
            const objetivo = 1600;
            const escala = img.width ? Math.min(2.5, Math.max(1, objetivo / img.width)) : 1;
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(img.width * escala);
            canvas.height = Math.round(img.height * escala);
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            try {
                const datos = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const d = datos.data;
                for (let i = 0; i < d.length; i += 4) {
                    const gris = 0.299 * d[i] + 0.587 * d[i + 1] + 0.114 * d[i + 2];
                    // binarización suave: blancos claros, negros oscuros, gris medio se mantiene
                    const v = gris > 150 ? 255 : gris < 90 ? 0 : gris;
                    d[i] = d[i + 1] = d[i + 2] = v;
                }
                ctx.putImageData(datos, 0, 0);
            } catch (e) {
                // si el canvas está "tainted" u otro error, usamos la imagen sin filtro
            }
            URL.revokeObjectURL(url);
            resolve(canvas.toDataURL('image/png'));
        };
        img.onerror = () => {
            URL.revokeObjectURL(url);
            resolve(file);
        };
        img.src = url;
    });

// OCR de alto nivel: preprocesa la imagen y devuelve el texto reconocido.
window.ocrDesdeImagen = async (file, onProgress) => {
    const T = await window.cargarTesseract();
    const procesada = await window.preprocesarImagen(file);
    const { data } = await T.recognize(procesada, 'spa+eng', {
        logger: (m) => {
            if (m.status === 'recognizing text' && onProgress) {
                onProgress(Math.round(m.progress * 100) + '%');
            }
        },
    });
    return (data.text || '').trim();
};

// Escucha eventos "notify" que Livewire dispara desde el servidor
document.addEventListener('livewire:init', () => {
    Livewire.on('notify', (event) => {
        const data = Array.isArray(event) ? event[0] : event;
        window.toast({
            icon: data.type || 'success',
            title: data.message || 'Operación realizada',
        });
    });
});
