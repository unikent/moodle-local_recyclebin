# Moodle Recycle Bin
This plugin adds a "recycle bin" for course modules to Moodle.
It requires a core hack.

## Installation
As there is no pre-cm-deleted event, you will need to add a line to '/course/lib.php' (function course_delete_module), right after the first "if()".
```
diff --git a/course/lib.php b/course/lib.php
index e49bdf1..5f8d6e6 100644
--- a/course/lib.php
+++ b/course/lib.php
@@ -1654,6 +1654,9 @@ function course_delete_module($cmid) {
         return true;
     }
 
+    // Notify the recycle bin plugin.
+    \local_recyclebin\Observer::pre_cm_delete($cm);
+
     // Get the module context.
     $modcontext = context_module::instance($cm->id);
 
```

## Menu
![Image of Menu] (https://cloud.githubusercontent.com/assets/4242976/7748834/3c5cd14e-ffc0-11e4-8fea-4db5fa0319d9.png)
