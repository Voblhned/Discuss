$(function() {
   $('.dis-category-li').click(DISHome.toggleCategory);
});

var DISHome = {
    toggleCategory: function(id) {
        id = $(this).attr('id');
        $(this).parent().children('li.dis-board-li.'+id).slideToggle();
    }
};