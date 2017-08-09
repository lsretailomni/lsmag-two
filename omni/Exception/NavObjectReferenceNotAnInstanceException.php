<?php
namespace Ls\Omni\Exception;

use Exception;

class NavObjectReferenceNotAnInstanceException extends NavException
{
    // usually, this exception means that some kind of information is missing, e.g. one line in the XML is not set
}
