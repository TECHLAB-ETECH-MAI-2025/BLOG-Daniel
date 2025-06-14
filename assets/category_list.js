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
                data: 'actions',
                orderable: false,
                searchable: false
            }
        ]
    });
});
