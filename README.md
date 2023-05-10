## Commisosn Calculator

_____

### About
This is a simple commission calculator.

### How to run
1. Clone the repository:
2. Run:
    ```bash
    composer install && cp .env.example .env
    ```
3. Set the `CURRENCY_DOMAIN','WITHDRAWCOMMISSIONFEE','BUSINESSCOMMISSIONFEE', 'DEPOSITECOMMISSIONFEE` in the `.env`.
4. Copy the CSV file to the `storage`. In this directory 'app/public/input.csv'. The file is already there but you can try yours.If you would like to use the file that is already there add mentioned path after artisan command.
5. Run:
    ```bash
    php artisan commission-fee-calculation app/public/yourFileName.csv
    ```
### How to run tests
If you are using new file and outputs should be different. In env file there is a variable `RESULTFORTEST` which should be filled with correct answers.As an example: `0.6\n3\n0\n0.06\n1.5\n0\n0.69\n0.3\n0.3\n3\n0\n0\n8607.32\n`
1. Run:
    ```bash
    php artisan test
    ```
