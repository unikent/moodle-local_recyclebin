// Added a confirmation dialogue when the empty recycle bin
// button has been selected.
M.local_recyclebin = {
    init: function (Y) {
        // Get some strings from the Recycle bin lang file.
        var confirmstring = M.util.get_string('emptyconfirm', 'local_recyclebin');

        // Confirmation dialogue function.
        function confirmDelete(e) {
            // Prevent the button from immediately performing its action.
            e.preventDefault();

            // Get the URL that leads to emptying the recycle bin.
            var urldelete = this.get('href');

            // Add a confirm dialogue box.
            YUI().use('moodle-core-notification-confirm', function(Y) {
                var confirm = new M.core.confirm({
                    question: confirmstring,
                    center: true,
                    modal: true,
                });

                // Perform the button's action if "Yes" is selected.
                confirm.on('complete-yes', function() {
                    window.location = urldelete;
                }, this);

                // Render the confirm dialogue.
                confirm.render().show();
            });
        }

        // Perform this action when any "Delete" button/link is clicked.
        Y.all('.recycle-bin-delete').on('click', confirmDelete);

        // Find the "Delete All" button and perform an action when it is clicked.
        Y.one('.recycle-bin-delete-all').on('click', confirmDelete);
    }
};
