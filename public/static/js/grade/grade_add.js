$(function () {
    //当用户修改了输入框内容时才触发
    $("form").children().change(function () {
        $("#submit").removeClass('disabled');
    });

    //ajax方式提交当前表单数据
    $("#submit").on("click", function (event) {
        $.ajax({
            type: "POST",
            url: "/grade/checkAdd'",
            data: $("#form-grade-add").serialize(),
            dataType: "json",
            success: function (data) {
                if (data.status == 1) {
                    alert(data.message);
                } else {
                    alert(data.message);
                }
            }
        });
    });
});