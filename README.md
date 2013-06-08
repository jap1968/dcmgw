dcmgw is a script acting as a gateway allowing to do c-find requests to a Dicom server.
The results are returned as XML contents. The xml folder contains the XSD specification
of the response messages as well as some XML samples.

dcmgw relies upon dcmqr, from the [dcm4che2 toolkit](http://www.dcm4che.org/confluence/display/d2/dcm4che2+DICOM+Toolkit).

Dicom servers are intended to be accessed just by Dicom clients. This is one important limitation in order to access
these servers from web applications.

Currently, things are changing and there are new proposals in order to extend the Dicom protocol by means of
RESTful web services.

In particular, QIDO-RS (Query based on ID for DICOM Objects by RESTful Services) will be included
in Dicom [supplement 166](http://www.dclunie.com/dicom-status/status.html). Meanwhile, applications like dcmgw provide similar functionality allowing
to access Dicom servers from web applications right now.
