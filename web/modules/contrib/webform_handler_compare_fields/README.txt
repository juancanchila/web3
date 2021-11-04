CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers


INTRODUCTION
------------

The Webform Handler Compare Fields module adds a functionality that was
missing in Drupal 8 webform validation that allows comparing of different
fields to ensure they are equal/not equal/greater than/etc.  This handler
can be added to a webform multiple times to compare multiple different fields.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/webform_handler_compare_fields

 * To submit bug reports and feature suggestions, or track changes:
   https://www.drupal.org/project/issues/webform_handler_compare_fields


REQUIREMENTS
------------

Webform 8.x-5.x (tested with >8.x-5.8 but should work with earlier versions of
the 8.x-5.x branch)


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

To configure the interface

1.  Navigate to your webform's handlers page (e.g. 
    /admin/structure/webform/manage/_**form_id**_/h andlers).
2.  Click [+ Add handler] button.
3.  Click [Add handler] next to "Validate Entries by Comparing 2 fields"
4.  Enter the field names and comparison operator and data type information
    *   Machine name is generally just the key from the form build page.
    *   Allowable operators are "==", "!=", "<>", "<", "<=", ">", and ">=".
        While "===", and "!==" are included for completeness, they are of little
        value as units are hard converted before comparison.
    *   Nearly all webform values are strings by default, choose how they
        should be handled if they are different, this is especially critical
        for dates. Both sides of the comparison will be treated as the same
        type.
    *   At this time, both integers and floating point numbers are compared as
        floats.
5.  Click [Save]
6.  This handler can be added multiple times one one form by repeated the
    above steps.


TROUBLESHOOTING
---------------

Depending on the field, there are times that you want to put in the ID and
times that you want to put in the name.  Generally for form fields use the
name, for containers (e.g. fieldsets) use the ID.


MAINTAINERS
-----------

Current maintainers:
 * Kevin W. Finkenbinder (kwfinken) - https://www.drupal.org/u/kwfinken

Written By Kevin Finkenbinder while working for MSU.

COPYRIGHT © 2019  
MICHIGAN STATE UNIVERSITY BOARD OF TRUSTEES  
ALL RIGHTS RESERVED  

PERMISSION IS GRANTED TO USE, COPY, CREATE DERIVATIVE WORKS AND REDISTRIBUTE
THIS SOFTWARE AND SUCH DERIVATIVE WORKS FOR ANY PURPOSE, SO LONG AS THE NAME
OF MICHIGAN STATE UNIVERSITY IS NOT USED IN ANY ADVERTISING OR PUBLICITY
PERTAINING TO THE USE OR DISTRIBUTION OF THIS SOFTWARE WITHOUT SPECIFIC,
WRITTEN PRIOR AUTHORIZATION. IF THE ABOVE COPYRIGHT NOTICE OR ANY OTHER
IDENTIFICATION OF MICHIGAN STATE UNIVERSITY IS INCLUDED IN ANY COPY OF ANY
PORTION OF THIS SOFTWARE, THEN THE DISCLAIMER BELOW MUST ALSO BE INCLUDED.  

THIS SOFTWARE IS PROVIDED AS IS, WITHOUT REPRESENTATION FROM MICHIGAN STATE
UNIVERSITY AS TO ITS FITNESS FOR ANY PURPOSE, AND WITHOUT WARRANTY BY MICHIGAN
STATE UNIVERSITY OF ANY KIND, EITHER EXPRESS OR IMPLIED, INCLUDING WITHOUT
LIMITATION THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
PARTICULAR PURPOSE. THE MICHIGAN STATE UNIVERSITY BOARD OF TRUSTEES SHALL NOT
BE LIABLE FOR ANY DAMAGES, INCLUDING SPECIAL, INDIRECT, INCIDENTAL, OR
CONSEQUENTIAL DAMAGES, WITH RESPECT TO ANY CLAIM ARISING OUT OF OR IN
CONNECTION WITH THE USE OF THE SOFTWARE, EVEN IF IT HAS BEEN OR IS HEREAFTER
ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
