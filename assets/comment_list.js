import $ from 'jquery';
import 'datatables.net-bs5';
import 'datatables.net-responsive-bs5';

$(document).ready(function () {
    $('#comments-table').DataTable({
        serverSide: true,
        processing: true,
        responsive: true,
        ajax: {
            url: '/api/comment/data',
            type: 'POST',
        },
        columns: [
            { data: 'id' },
            { data: 'user', defaultContent: '' },
            { data: 'articleTitle', defaultContent: '' },
            { data: 'content' },
            { data: 'createdAt' },
            {
                data: 'actions',
                orderable: false,
                searchable: false
            }
        ]
    });
});
