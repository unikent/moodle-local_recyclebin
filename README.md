# moodle-local_recyclebin
Recycle bin for Kent Moodles

To get this working you need to add this to '/course/lib.php' (function course_delete_module), right after the first "if()".
```
\local_recyclebin\Observer::pre_cm_delete($cm);
```

## Menu
![Image of Menu] (https://cloud.githubusercontent.com/assets/4242976/7748834/3c5cd14e-ffc0-11e4-8fea-4db5fa0319d9.png)
