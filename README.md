This is a project which uses an ESP-01S board and a DHT22 temperature and humidity sensor to record information about the climate inside greenhouses. This project includes C++ files for the ESP-01S, PHP API files for the server and a Java andorid studio project for a mobile front end.

Multiple sensors can be added by assigning a new access token and ID. these will then post data via wifi to the PHP API which will save the readings into a MySQL database.  A cron job is used to calculate average day and nigh temperatures and this calculated data is stored for each day.  The mobile App pulls down latest data for each greenhouse and tapping a greenhouse will open detailed calculations for the past week.

All data is based upon sunrise and sunset times, which change daily. Average night calculations for any given day will begin at sunset on the previous evening and finish at sunrise on the given day.  Daily temperatures will range between runrise and sunset on a given day. 

This project was developed for a cactus collector and the calculations are specific to their needs.  For example, the average night time temps over a week are used to decide if it's safe to water the cactus (low temps may freeze the water and damage the roots).
