# glacier-multipart-upload-abstraction
A PHP website to upload files to Amazon S3 Glacier using multipart abstraction with functionality to resume failed uploads.

#### References:
https://aws.amazon.com/blogs/developer/uploading-archives-to-amazon-glacier-from-php/

#### Instructions:
Open GlacierMultipartUpload.html on your browser and enter necessary details.  You need to specify an existing vault name and Access Key ID and Secret Access Key of a user who has appropriate access to the vault.
- Part Size in MiB => Size of each part in Mebibytes (default is 1)
- Concurrency      => Number of files to upload at a time in parallel (default is 1)
