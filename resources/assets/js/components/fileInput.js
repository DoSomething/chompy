// // @TODO - stolen from code pen need to rewrite without Jquery.
// function bs_input_file() {
//     console.log('this has to be working at least');
//     $(".input-file").before(
//         function () {
//             console.log(!$(this).prev().hasClass('input-ghost'));
//             if (!$(this).prev().hasClass('input-ghost')) {
//                 console.log('yes');
//                 var element = $("<input type='file' class='input-ghost' style='visibility:hidden; height:0'>");
//                 element.attr("name", $(this).attr("name"));
//                 element.change(function () {
//                     element.next(element).find('input').val((element.val()).split('\\').pop());
//                 });
//                 $(this).find("button.btn-choose").click(function () {
//                     element.click();
//                 });
//                 $(this).find("button.btn-reset").click(function () {
//                     element.val(null);
//                     $(this).parents(".input-file").find('input').val('');
//                 });
//                 $(this).find('input').css("cursor", "pointer");
//                 $(this).find('input').mousedown(function () {
//                     $(this).parents('.input-file').prev().click();
//                     return false;
//                 });
//                 return element;
//             }
//         }
//     );
// }
// $(function () {
//     bs_input_file();
// });