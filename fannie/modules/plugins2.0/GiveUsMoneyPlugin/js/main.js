function getEndDate()
{
    var start = new Date($('#loandate').val());
    // normalize
    $('#loandate').val(dateToYMD(start));
    var months = Number($('#term').val());
    start.setMonth(start.getMonth() + months);
    $('#enddate').html(dateToYMD(start));
}

function dateToYMD(dt) {
    var d = dt.getDate()+1;
    var m = dt.getMonth() + 1;
    var y = dt.getFullYear();
    return '' + y + '-' + (m<=9 ? '0' + m : m) + '-' + (d <= 9 ? '0' + d : d);
}

function getDefaultRate(amount) {
    $.ajax({
        url: 'GumMainPage.php',
        method: 'get',
        data: 'rateForAmount='+amount,
        success: function(resp) {
            $('#rate').val(resp);
        }
    });
}

function confirmNewLoan()
{
    var amount = $('#principal').val();
    if (isNaN(amount) || amount == 0) {
        alert('Error: principal amount is not a number');
        return;
    }
    var term = $('#term option:selected').html();
    var start = $('#loandate').val();
    var rate = $('#rate').val();
    if (isNaN(rate) || rate==='') {
        alert('Error: interest rate is not a number');
        return;
    }

    var msg = "You are about to create a new loan.\n";
    msg += "Principal Amount: $" + amount + "\n";
    msg += "Term: " + term + "\n";
    msg += "Interest Rate: " + rate + "%\n";
    msg += "Start Date: " + start + "\n";

    if (confirm(msg)) {
        console.log($('#loanform'));
        console.log($('#loanform').attr('action'));
        $('#loanform').submit();
    }
}

function updateEquityTotal(val)
{
    val = parseInt(val);
    $('#totalForShares').html(val * Number($('#shareSize').val()));
}

