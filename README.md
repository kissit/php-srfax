
## PHP-SRFax

A simple class that provides a PHP interface to the SRFax web API. Usage requires a valid account.  SRFax provides a reliable internet fax service for a reasonable price, including top notch support.

SRFax API Homepage: https://www.srfax.com/online-fax-features/internet-fax-api/
SRFax API Documentation: https://www.srfax.com/srf/media/SRFax-REST-API-Documentation.pdf

### Installation

Simply clone/download the repo.  The only file required is srfax.php.  The only external requirement of the class is the standard PHP cURL module. 

### Examples

A script is also provided that shows a call of each function for example/testing purposes.  To use this script, do the following:

1. Configure the required settings in your environment (or edit example/example.php to suit).  Note that by default this example script will send test faxes to http://faxtoy.net which is configurable in example.php.
```
export API_USER="<SRFAX_CUST_NUMBER>"
export API_PASS="<SRFAX_PASSWORD>"
export SENDER_FAX="<SENDER_FAX_NUMBER>"
export SENDER_EMAIL="<SENDER_EMAIL>"
```

2. Run the script with no parameters to see the options
```
$ php example.php

This tool is used to run examples of the SRFax API lib.  Options are as follows
        --help=Show this help screen
        --func=Name of func to run (required)
        --id=FaxDetailsID to use for func (if applicable)
        --file=File to use for input to send a fax (if applicable)
        --viewed=Viewed state to set for fax - Y or N (if applicable)
        --dir=Direction to use for func - IN or OUT (if applicable)
```

3. To list the faxes in your inbox:
```
php example.php --func=inbox
```

4. To queue a fax, copy a test fax file to the example directory:
```
$ php example.php --func=queue --file=testfax.pdf
Successfully queued fax id: 18809544
```

5. To check the status of the queued fax:
```
$ php example.php --func=status --id=18809544
```

6. To retrieve a fax from your inbox:
```
$ php example.php --func=retrieve --id=18709521 --dir=IN
Successfully retrieved file as 18709521.pdf
```

Copyright (C) 2015 KISS IT Consulting, LLC.  All rights reserved.