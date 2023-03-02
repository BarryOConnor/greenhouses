package com.barry.darrensgreenhouses;

import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Date;

public class GHReading {
    private String id;
    private String temperature;
    private String humidity;
    private String taken;

    public String getId() {
        return id;
    }

    public String getTemperature() {
        return temperature;
    }

    public void setTemperature(String temperature) {
        this.temperature = temperature;
    }

    public String getHumidity() {
        return humidity;
    }

    public void setHumidity(String humidity) {
        this.humidity = humidity;
    }

    public String getTaken() {
        return taken;
    }

    public void setTaken(String taken) {
        this.taken = taken;
    }

    public String getTakenFormatted() throws ParseException {
        SimpleDateFormat dateFormatPrev = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
        Date d = dateFormatPrev.parse(this.taken);
        SimpleDateFormat dateFormat = new SimpleDateFormat("dd/MM/yyyy HH:mm:ss");
        String changedDate = dateFormat.format(d);
        return changedDate;
    }

    public GHReading() {}

    public GHReading(String pId, String pTemperature, String pHumidity, String pTaken) {
        id = pId;
        temperature = pTemperature;
        humidity = pHumidity;
        taken = pTaken;
    }
}
