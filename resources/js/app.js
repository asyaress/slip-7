import './bootstrap';
import Swal from 'sweetalert2';

window.appDialogs = {
    async confirm({
        title,
        text,
        confirmText = 'Lanjutkan',
        cancelText = 'Batal',
        icon = 'question',
    }) {
        const result = await Swal.fire({
            title,
            text,
            icon,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            reverseButtons: true,
            focusCancel: true,
            confirmButtonColor: '#781a38',
            cancelButtonColor: '#cbd5e1',
        });

        return result.isConfirmed;
    },
};
