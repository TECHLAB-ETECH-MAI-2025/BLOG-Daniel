import $ from 'jquery';
import 'datatables.net-bs5';
import 'datatables.net-responsive-bs5';

$(document).ready(function () {
    $('#users-table').DataTable({
        serverSide: true,
        processing: true,
        responsive: true,
        ajax: {
            url: '/admin/user/data',
            type: 'POST',
        },
        columns: [
            { data: 'id' },
            { data: 'email' },
            { data: 'fullName' },
            { data: 'roles' },
            { data: 'createdAt' },
            {
                data: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json'
        }
    });
});
