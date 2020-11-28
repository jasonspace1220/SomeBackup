$(function () {
    let table = $("#personal_tbl").DataTable({
        serverSide: true,
        ajax: {
            "type": "POST",
            "url": API_URL.contact_reservation_dt,
        },
        fixedHeader: true,
        responsive: true,
        searching: true,
        searchDelay: 1500,
        autoWidth: true,
        pageLength: 5,
        // order: [1, "desc"],
        // "order": [] ,
        "language": dt_language,
        "processing": true,
        oLanguage: {
            sProcessing: `<div class="alert alert-secondary" role="alert" style="margin:0px;">
                        <i class="fas fa-circle-notch fa-spin"></i>
                    </div>`
        },
        columns: [{
                "data": "customer_name"
            },
            {
                "mData": "customer_phone",
                "mRender" : function(d){
                    return `
                        <div onclick="add(${d.customer_id},null)">${d.phone}</div>
                    `;
                }
            },
            {
                "data": "customer_id"
            },
            {
                "data": "next_contact_date"
            },
            {
                "data": "next_contact_meno"
            },
            {
                "data": "last_contact_datetime"
            },
            {
                "data": "process_status"
            },
            {
                "data": "sales_from_plat"
            },
            {
                "mData": "action",
                "mRender": function (d) {
                    let edit = `<button class="btn btn-info btn-sm iconbtn" type="button" style="margin-left: 2px;" onclick="edit(${d.row_id},this)">
                                    <i class="fas fa-pen" data-toggle="tooltip" data-placement="top" title="編修聯繫紀錄" ></i>
                                    <span class="spinner-border spinner-border-sm hide"></span>
                                </button>`;
                    let del = `<button class="btn btn-info btn-sm iconbtn" type="button" style="margin-left: 2px;" onclick="del(${d.row_id},this)">
                                    <i class="far fa-trash-alt" data-toggle="tooltip" data-placement="top" title="刪除"></i>
                                    <span class="spinner-border spinner-border-sm hide"></span>
                                </button>`;
                    let add = `<button class="btn btn-info btn-sm iconbtn" type="button" style="margin-left: 2px;" onclick="add(${d.customer_id},this)">
                                    <i class="fas fa-phone-square" data-toggle="tooltip" data-placement="top" title="新增聯繫紀錄" ></i>
                                    <span class="spinner-border spinner-border-sm hide"></span>
                                </button>`;
                    let detail = `<button class="btn btn-info btn-sm" type="button" style="margin-left: 3px;width:30.25px;border-radius: .2rem;" onclick="detail_btn(${d.customer_id},this)">
                                <i class="fas fa-user-alt user_edit_icon"  data-toggle="tooltip" data-placement="top" title="詳細資料"></i>
                                <span class="spinner-border spinner-border-sm hide"></span>
                            </button>`;
                    return `
                        ${detail}
                        ${edit}
                        ${del}
                        ${add}
                    `;
                }
            },

        ],
        columnDefs: [{
            "targets": 0,
            "orderable": false
        }],

        drawCallback: function () {
            $('[data-toggle="tooltip"]').tooltip({
                container: 'body'
            });
        },
        rowCallback: function( row, data ) {
            if(data.action.gray == true){
                $(row).addClass('cus_gray');
            }
        }
    });
    /* 動態欄位調整     */
    let col_to_name = [
        '姓名',
        '手機',
        '客戶編號',
        '預約聯繫日期',
        '預約聯繫備註',
        '最後聯繫日期',
        '進度狀態',
        '業務|來源|平台',
        '操作',
    ];
    let default_unchk_array = [
        2
    ];
    addDtDropDownBtn("personal_tbl", col_to_name, table, default_unchk_array);
    $("#personal_tbl_filter > label").toggleClass('hide', true);

    $("#edit_save").click(function () {
        edit_btn_click($(this));
    })

    personal_today_click(table);
})


function personal_today_click(table) {
    $(".per_filter_btn").click(function () {
        table.search($(this).val()).draw();
    })
}


function edit(row_id, btn) {
    edit_scheduled_contact_btn(row_id,$(btn));
}


function add(row_id, btn) {
    scheduled_contact_btn(row_id,$(btn));
}

function del(row_id, btn) {
    Swal.fire({
        title: '刪除預約',
        text: "確定要刪除預約?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: '取消',
        confirmButtonText: '刪除'
    }).then((result) => {
        if (result.value) {
            btn_loading2($(btn),'show');
            $.ajax({
                url:API_URL.delete_contact + '/' + row_id,
                type:"POST",
            }).done(function(d){
                if(d.status == 'success'){
                    serverSuccessAlert('成功','刪除成功',function(){
                        location.reload();
                    });
                }else{
                    serverErrorAlert();
                }
            }).fail(function(){
                serverErrorAlert();
            }).always(function(){
                btn_loading2($(btn),'hide');
            })
        }
    })
}
// function confirmCheck(title,text,func){
//     Swal.fire({
//         title: '發送簡訊',
//         text: "確定要發送簡訊至" + phone_number + "?",
//         icon: 'warning',
//         showCancelButton: true,
//         confirmButtonColor: '#3085d6',
//         cancelButtonColor: '#d33',
//         cancelButtonText: '取消',
//         confirmButtonText: '發送'
//     }).then((result) => {
//         if (result.value) {
//             func();
//         }
//     })
// }
