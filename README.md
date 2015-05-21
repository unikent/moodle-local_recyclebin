# moodle-local_recyclebin
Recycle bin for Kent Moodles

To get this working you need to add this to '/course/lib.php' (function course_delete_module), right after the first "if()".
```
\local_recyclebin\Observer::pre_cm_delete($cm);
```
