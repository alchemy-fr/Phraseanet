# Stamper

Adds banner(s) on document or previews downloads, to include information like a logo, text or metadata.

Stamp is possible on most bitmap image documents (jpeg, png, gif, ...), but may not work on specific formats like multi-layers tif.

To configure Stamper, edit the collection’s setting using the XML view in Collection settings section (the user must have Manage value lists right applied).

### stamp
Each `stamp` block configures one banner, declaring its position:
- position="TOP": On top of the image
- position="BOTTOM": Under the image
- position="TOP-OVER": On top, over the image (to use with a (semi)transparent background color)
- position="BOTTOM-OVER"

One can define the color of the background

```xml
<stamp position="BOTTOM-OVER" background="255,255,255,32">
    ...
</stamp> 
```

### Adding a logo:
First upload a logo (jpg, png) using the Admin interface in the corresponding collection(s).

Declare the logo inside the stamp block, set to the left side of the banner.
```xml
<logo position="left" width="25%"/>     <!-- 1/4 of the image width -->
```

### Adding lines of text:
Each `<text...>` block defines a line of text
```xml
<!-- big white text with transluant black shadow --> 
<text size="150%" color="255,255,255,0" shadow="0,0,0,64">Copyright NASA</text>
```

text can **include** variable parts, like **field** value (metadata) from the record, or technical
**var**iables like the record_id or the date of export
```xml
<text size="50%" color="0,0,0,0">Credit: <field name="Credit" /></text>
<text size="50%" color="0,0,0,0">Record-id: <var name="RECORD_ID" /> exported on <var name="DATE" /></text>
```



### About colors
Colors are expressed as `"R,G,B,t"`, with R,G,B: 0...255 ; t is the transparency, with 0: opaque...127: transparent.

t can be ommited, in case the color is opaque.

### About shadow (text)
The plain-colored text can be unreadable if its color matches the image color.

Setting an opposite shadow color will enhance readability. Printing semi-transparant text over a shadow can
simulate a 3D look.


### About sizes
Size applies to logo (`width` attribute) or text (`size` attribute).

Sizes can be expressed as asolute (e.g. `width="100"`) or relative to the image width (e.g. `width="25%"`).

Because one can download a hi-res document like 6000 * 4000 pixels, or a smaller preview like 800 * 600, using relative
sizes will generate stamps with similar "look" relative to the image size.

For a `logo`, the relative size `width="25%"` will render the logo as 1/4 of the width of the image.

For a `text`, the `size="100%"` will fit ~60 characters on the image width.
This ensures a readable text even for small size previews.



