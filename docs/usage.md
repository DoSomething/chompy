# How to use Chompy

## Importing a file

Once Chompy is set up locally, you can select a .csv file from  your computer, and submit it to be added to the queue where it will be stored in `storage/app/uploads`. You can add multiple files to be processed by the queue. Then, run `php artisan queue:work` to send these imported files to your database. To clear the queue, run 
1. `redis-cli`
2. `FLUSHDB`
3. `exit`
