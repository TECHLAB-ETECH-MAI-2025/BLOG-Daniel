import $ from 'jquery';
import 'datatables.net-bs5';
import 'datatables.net-responsive-bs5';

$(document).ready(function () {
    $('#categories-table').DataTable({
        serverSide: true,
        processing: true,
        responsive: true,
        ajax: {
            url: '/api/category/data',
            type: 'POST',
        },
        columns: [
            { data: 'id' },
            { data: 'title' },
            { data: 'description' },
            { data: 'createdAt' },
            {
                data: 'id',
                render: function (data, type, row) {
                    return `
                        <a href="/category/${data}">Afficher</a> |
                        <a href="/category/${data}/edit">Modifier</a>
                    `;
                },
                orderable: false,
                searchable: false,
            }
        ]
    });
});
