//上線中OR下線
var ISONLINE = false;
var DT_DOM =
    '<"top row" <"col-md-6 addToCacheArea"><"col-md-6" f>>rt<"bottom row" <"col-md-6" il><"col-md-6" p>><"clear">';
var DT_DOM_WITHOUT_ADDCACHE =
    '<"top row" <"col-md-6 emptyArea"><"col-md-6" f>>rt<"bottom row" <"col-md-6" il><"col-md-6" p>><"clear">';

let dt_language = {
    infoFiltered: "",
    zeroRecords: "沒有符合的結果",
    infoEmpty: "顯示第 0 至 0 項結果，共 0 項",
    info: "共 _TOTAL_ 筆 ( 顯示 _START_ 到 _END_ )",
    //info: "第 _PAGE_ / _PAGES_ 頁",
    paginate: {
        previous: "<",
        next: ">",
    },
    lengthMenu: "一次顯示: _MENU_",
    select: {
        // 'rows': {
        //     '_': '%d rows selected',
        //     '0': '',
        //     '1': '%d 選擇'
        // }
        rows: {
            _: "",
            0: "",
            1: "",
        },
    },
};
//讓按鈕點了後 loading和disable效果
function btn_loading(btn = false, trig) {
    let spinner_temp = "";
    spinner_temp = `<span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>`;
    if (btn) {
        if (trig == "show") {
            btn.prop("disabled", true);
            btn.append(spinner_temp);
        } else {
            btn.find(".spinner-grow").remove();
            btn.prop("disabled", false);
        }
    } else {
        let btn = $("button > .spinner-grow").parent();
        btn.find(".spinner-grow").remove();
        btn.prop("disabled", false);
    }
}

function btn_loading2($_btn, trig = false) {
    let spinner = $_btn.children("span.spinner-border");
    let textOrIcon = $_btn.children();

    if (trig == true || trig == "show") {
        $_btn.prop("disabled", true);
        $(textOrIcon).toggleClass("hide", true);
        $(spinner).toggleClass("hide", false);
    } else {
        $_btn.prop("disabled", false);
        $(textOrIcon).toggleClass("hide", false);
        $(spinner).toggleClass("hide", true);
    }
}

//Loading畫面
function loading_mask(trig) {
    let mask = $("#loading_mask");
    let loader = $("#loading_loader");
    if (trig == "show") {
        let height = $("html").prop("scrollHeight");
        $("body").css({
            "overflow-y": "hidden",
        });
        mask.height(height + "px");
        mask.toggleClass("hide", false);
        loader.toggleClass("hide", false);
    } else {
        $("body").css({
            "overflow-y": "scroll",
        });
        mask.toggleClass("hide", true);
        loader.toggleClass("hide", true);
    }
}

//去除字串最後符號 '/'
function rtrim(str) {
    return str.replace(/(\/*$)/g, "");
}

//錯誤Alert
function serverErrorAlert(title = false, msg = false, func = false) {
    if (title == false) {
        title = "錯誤";
    }
    if (msg == false) {
        msg = "Server Error請重新整理!";
    }
    Swal.fire(title, msg, "error").then(() => {
        if (func !== false) {
            func();
        }
    });
}

//警告
function serverWarning(title = false, msg = false, func = false) {
    if (title == false) {
        title = "警告";
    }
    if (msg == false) {
        msg = "Server Server不准你這樣幹!";
    }
    Swal.fire(title, msg, "warning").then(() => {
        if (func !== false) {
            func();
        }
    });
}
//成功Alert
function serverSuccessAlert(title = false, msg = false, func = false) {
    if (title == false) {
        title = "成功";
    }
    if (msg == false) {
        msg = "操作成功";
    }
    Swal.fire(title, msg, "success").then(() => {
        if (func !== false) {
            func();
        }
    });
}

/* DataTable Dynamic Col 動態顯示欄位-------------------------------------------------------------------------- */
/**
 * table_id => dataTable html 的 id
 * col_name => 依照順序的欄位名稱陣列
 *      EX: let col_to_name = [
                    '編號',
                    '公布人',
                    '標題',
                    '張貼日期',
                    '內容',
                    '起始日期',
                    '結束日期',
                    '檔案連結',
                    '操作',
                ];

    table => dataTable 的實例(?) 我忘了這叫啥了
                EX:
                let table = $(table_id).DataTable();
 */
function addDtDropDownBtn(
    table_id,
    col_name_array,
    table,
    default_unchk_array = false,
    needColSearch = false
) {
    /**長出內容 */
    let chk_list_html_tmp = ``;
    $.each(col_name_array, function (k, v) {
        let is_checked = "checked";
        if (
            default_unchk_array != false &&
            default_unchk_array.indexOf(k) != -1
        ) {
            is_checked = "";
            let column = table.column(k);
            column.visible(!column.visible());

            if (needColSearch) {
                if (column.visible()) {
                    $(`.tbl_search[key='${k}']`)
                        .parent()
                        .toggleClass("hide", false);
                    $(`.tbl_select[key='${k}']`)
                        .parent()
                        .toggleClass("hide", false);
                } else {
                    $(`.tbl_search[key='${k}']`)
                        .parent()
                        .toggleClass("hide", true);
                    $(`.tbl_select[key='${k}']`)
                        .parent()
                        .toggleClass("hide", true);
                }
            }
        }

        chk_list_html_tmp += `
        <div class="custom-control custom-checkbox mr-sm-2">
            <input type="checkbox" class="custom-control-input dt_chk" data-key=${k} id="${table_id}_list_${k}" ${is_checked}>
            <label class="custom-control-label" for="${table_id}_list_${k}">${v}</label>
        </div>`;
    });

    /**長初下拉按鈕和內容 */
    $(`#${table_id}_filter`).append(`
    <button class="btn form-control-sm dt_col_dropbtn" type="button" id="${table_id}_col_dropdown" data-toggle="dropdown" aria-expanded="false">
        <i class="fab fa-buromobelexperte"></i>
    </button>
    <div class="dropdown-menu dt_col_dropdown" aria-labelledby="${table_id}_col_dropdown" id="${table_id}_col_dropdown_menu">
        <form class="px-4 py-3">
            ${chk_list_html_tmp}
        </form>
    </div>
 `);
    /**
     * 避免 bootstrap Dropdown 因為點了內容而自動消失
     */
    $(".dropdown-menu").on("click", function (event) {
        event.stopPropagation();
    });

    /**
     * 勾選 控制顯示隱藏的監聽
     */
    if (needColSearch) {
        dt_chk_with_colSearch_listen(table);
    } else {
        dt_chk_listen(table);
    }
}

function dt_chk_listen(table) {
    $(".dt_chk").on("click", function (e) {
        // Get the column API object
        var column = table.column($(this).attr("data-key"));
        // Toggle the visibility
        column.visible(!column.visible());
    });
}

function dt_chk_with_colSearch_listen(table) {
    $(".dt_chk").on("click", function (e) {
        // Get the column API object
        var column = table.column($(this).attr("data-key"));
        // Toggle the visibility
        column.visible(!column.visible());

        if (column.visible()) {
            $(`.tbl_search[key='${$(this).attr("data-key")}']`)
                .parent()
                .toggleClass("hide", false);
        } else {
            $(`.tbl_search[key='${$(this).attr("data-key")}']`)
                .parent()
                .toggleClass("hide", true);
        }
    });
}

function dt_set_default_notCheck(table_id, default_chk_array) {
    /*
        設定default
    */
    if (default_chk_array) {
        $.each(default_chk_array, function (kk, vl) {
            $(`${table_id}_list_${vl}`).trigger("click");
            // console.log(`${table_id}_list_${vl}`);
        });
    }
}

function stopPropagation(evt) {
    if (evt.stopPropagation !== undefined) {
        evt.stopPropagation();
    } else {
        evt.cancelBubble = true;
    }
}

/** 上縣排班*/
function setUserOnlineApi() {
    $.ajax({
        url: API_URL.set_user_online,
        type: "GET",
        success: function (d) {
            if (d.status == "success") {
                ISONLINE = true;
                if ($("#user_list_block").length) {
                    getAllApi();
                }
            }
        },
    });
}
/**下線 */
function setUserOfflineApi() {
    $.ajax({
        url: API_URL.set_user_offline,
        type: "GET",
        success: function (d) {
            ISONLINE = false;
            if (d.status == "success") {
                popSalesApi();
                if ($("#user_list_block").length) {
                    getAllApi();
                }
            }
        },
    });
}
/*取得目前狀態API*/
function getAllApi() {
    $.ajax({
        url: API_URL.all_online_user,
        type: "GET",
        success: function (d) {
            if (d.status == "success") {
                $("#user_list_block").empty();
                if (d.data != "N") {
                    $.each(d.data, function (k, v) {
                        let tmp = listBlockTemp(k, v);
                        $("#user_list_block").append(tmp);
                    });
                }
            }
        },
    });
}
/**檢查使用者還在線嗎 */
function checkUserOnline() {
    $.ajax({
        url: API_URL.check_user_online,
        type: "GET",
        success: function (d) {
            if (d.status == "success") {
                if (d.data) {
                    $("#switchOnlineLabel").text(SHIFT_TEXT.online);
                    $("#switchOnline").prop("checked", true);
                    ISONLINE = true;
                } else {
                    $("#switchOnlineLabel").text(SHIFT_TEXT.offline);
                    $("#switchOnline").prop("checked", false);
                    popSalesApi();
                    ISONLINE = false;
                }
            }
        },
    });
}
/**
 *業務的離開排班API
 */
function popSalesApi() {
    $.ajax({
        url: API_URL.pop_sales,
        type: "GET",
        success: function (d) {
            if (d.status == "success") {
                if ($("#business_queue_list").length > 0) {
                    show_sales_queue_API();
                }
            }
        },
        error: function (e) {},
    });
}

/**
 * AJAX 錯誤自動顯示
 */
function ajaxShowError(e, defaultFunc = 0) {
    if (ERROR_MSG.hasOwnProperty(e.responseJSON.msg)) {
        if (defaultFunc == 0) {
            Swal.fire("錯誤", ERROR_MSG[e.responseJSON.msg], "error");
        } else {
            defaultFunc();
        }
    } else {
        serverErrorAlert();
    }
}

$(function () {
    $("#switchOnline").change(function () {
        if ($(this).is(":checked")) {
            setUserOnlineApi();
            $("#switchOnlineLabel").text(SHIFT_TEXT.online);
        } else {
            setUserOfflineApi();
            $("#switchOnlineLabel").text(SHIFT_TEXT.offline);
        }
    });
    checkUserOnline();
    setInterval(function () {
        if (ISONLINE == true) {
            checkUserOnline();
        }
    }, 10000);
});
