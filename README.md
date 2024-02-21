# Karma8

As was expected the code has been written with double ASAP: As Soon as Possible and As Stupid As Possible -  no fancy
Not tested well!

## Solution abstract
The proposed solution leans more towards proof of concept rather than full implementation, but it appears to be sufficient for the case.
The primary architectural concept involves dividing tasks into four processes to address various bottlenecks associated with external functions:
1. Scheduling email validation occurs without parallelization, given the task's constraints. Valid for subscriptions are expiring in 5 days (2 extra days before sending).
2. Scheduling email sending also lacks parallelization, akin to the validation process.
3. Consumption of validation jobs supports parallelization and is deemed necessary.
4. Consumption of email sending jobs similarly supports parallelization and is necessary.
5. Balancer to increase/decrease of parallel runs

## Case analysis
The most demanding scenario for scheduling involves managing 5,000,000 users, with only 20% holding subscriptions, resulting in 1,000,000 users. Among them, 15% have confirmed emails and require no further validation, leaving us with 850,000 users. With the cron job executing every minute, totaling 1440 times a day, we need to validate around 500 users per minute. A similar volume is expected for scheduling email sending, where 1000 per minute is presently adequate.

Regarding validation, the most challenging scenario arises due to potential delays of up to 1 minute, limiting us to validate with one cron task up to 1440 times daily. Clearly, parallelization and multi-processing are essential. Ideally, around 600 processes per day would be necessary, a number that surpasses practical limits without infrastructure auto-scaling. However, even 10 parallel processes are likely sufficient for the most demanding cases.

The situation for sending emails mirrors that of validation.

## Solution
Due to variations in average execution times and expected volumes, it's advisable to handle validation and sending emails separately. The queue pattern is implemented minimally, focusing on the main requirement of accommodating multiple consumers in parallel. As previously discussed, CRON serves as the consumer for these jobs.

### CRON configuration for schedule related jobs
```
echo "* * * * * php /path_to/schedule_validate_email.php" >> /etc/crontab
echo "* * * * * php /path_to/schedule_send_email.php" >> /etc/crontab
```
### CRON configuration for consuming related jobs
```
echo "* * * * * bash /path_to/consume-email-validation" >> /etc/crontab
echo "* * * * * bash /path_to/consume-email-sending" >> /etc/crontab
```
NOTE: There can be multiple CRON runs!!!

## Improvements

The current solution appears insufficient, prompting recommendations for enhancements:

- Implement retry logic to prevent redundant validation attempts.
- Introduce logging and monitoring mechanisms to ensure user data integrity.
- Consider leveraging a message broker such as RabbitMQ instead of relying solely on the database.
- Enhance auto-scaling capabilities by developing scripts that dynamically adjust the number of parallel processes based on workload demands.
- Explore additional optimization opportunities to further refine the system's performance and functionality.
- Rotation mechanisms for jobs and user_jobs
