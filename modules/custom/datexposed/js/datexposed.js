/**
 * @file
 * Provides JavaScript for Inline Entity Form.
 */

(function ($) {

    $('#edit-month,#edit-year').on('change', function () {
        var monthValue = $('#edit-month').val();
        var yearValue = $('#edit-year').val();
        var fromDate = generateFromDate(monthValue,yearValue);
        var toDate = generateToDate(monthValue,yearValue);

        $("#edit-field-release-date-value").val(fromDate);
        $("#edit-field-release-date-value-1").val(toDate);
    });

    function generateFromDate(date, year) {
        return year+"-"+date+"-01";
    }

    function generateToDate(date, year) {
        return year+"-"+date+"-31";
    }



    $("#edit-submit-filter-by-date").on('click', function () {
        var monthValue = $('#edit-month').val();
        var yearValue = $('#edit-year').val();
        if((monthValue==0 && yearValue >= 1) || (monthValue>=1 && yearValue == 0) ) {
            alert ("Select Both Month and Year");
            return false;
        }
    });


})(jQuery);
