Slideshow
=========

WordPress plugin

Incomplete and in development. Presently adds a meta box to Pages where you can select slides. Image IDs are then saved to post meta.
No front-end output yet.

To Do
-----
 - front-end template tag
 - fix lingering 'gallery' refernces
 - styles for meta box


```
has_slideshow( $id );
```
*$id* post id

```
slideshow( $id, $size );
```
*$id* post id  
*$size* image size

```
get_slideshow( $id, $size );
```
*$id* post id  
*$size* image size