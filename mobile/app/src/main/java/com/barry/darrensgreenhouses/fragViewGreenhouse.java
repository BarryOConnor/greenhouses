package com.barry.darrensgreenhouses;

import android.app.ProgressDialog;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;

import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.android.volley.RequestQueue;
import com.android.volley.Response;
import com.android.volley.VolleyError;
import com.android.volley.toolbox.StringRequest;
import com.android.volley.toolbox.Volley;
import com.google.android.material.snackbar.Snackbar;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Calendar;
import java.util.Date;
import java.util.List;
import java.util.Locale;

public class fragViewGreenhouse extends Fragment implements SwipeRefreshLayout.OnRefreshListener {
    private SwipeRefreshLayout mSwipeRefreshLayout;

    private TextView mtvGreenhouse;
    private TextView mtvTodayDate;
    private TextView mtvTodayAvgDayTemp;
    private TextView mtvTodayAvgNightTemp;
    private TextView mtvTodayMinTemp;
    private TextView mtvTodayMaxTemp;
    private TextView mtvTodayMinHumid;
    private TextView mtvTodayMaxHumid;

    private TextView mtvYesterdayDate;
    private TextView mtvYesterdayAvgDayTemp;
    private TextView mtvYesterdayAvgNightTemp;
    private TextView mtvYesterdayMinTemp;
    private TextView mtvYesterdayMaxTemp;
    private TextView mtvYesterdayMinHumid;
    private TextView mtvYesterdayMaxHumid;

    private TextView mtvWeekDate;
    private TextView mtvWeekAvgDayTemp;
    private TextView mtvWeekAvgNightTemp;
    private TextView mtvWeekMinTemp;
    private TextView mtvWeekMaxTemp;
    private TextView mtvWeekMinHumid;
    private TextView mtvWeekMaxHumid;

    private String url = "***REMOVED***/api.php?c=data&a=***REMOVED***&g=";
    private ProgressDialog dialog;

    public fragViewGreenhouse() {
        // Required empty public constructor
    }

    public static fragViewGreenhouse newInstance() {
        fragViewGreenhouse fragment = new fragViewGreenhouse();
        return fragment;
    }
    /**
     * This method is called when swipe refresh is pulled down
     */
    @Override
    public void onRefresh() {

        // Fetching data from server
        loadGreenhouseData();
    }


    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
    }

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
        View rootView = inflater.inflate(R.layout.fragment_view_greenhouse, container, false);

        // SwipeRefreshLayout
        mSwipeRefreshLayout = (SwipeRefreshLayout) rootView.findViewById(R.id.swipe_container);
        mSwipeRefreshLayout.setOnRefreshListener(this);

        /**
         * Showing Swipe Refresh animation on activity create
         * As animation won't start on onCreate, post runnable is used
         */
        mSwipeRefreshLayout.post(new Runnable() {

            @Override
            public void run() {

                mSwipeRefreshLayout.setRefreshing(true);

                // Fetching data from server
                loadGreenhouseData();
            }
        });

        // Inflate the layout for this fragment
        return rootView;
    }

    public void onViewCreated(View view, Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);
        DGH dgh = (DGH) getContext();
        url += dgh.getCurrentGreenhouse();

        mtvGreenhouse = view.findViewById(R.id.tvGreenhouse);
        mtvTodayDate = view.findViewById(R.id.tvTodayDate);
        mtvTodayAvgDayTemp = view.findViewById(R.id.tvTodayAvgDayTemp);
        mtvTodayAvgNightTemp = view.findViewById(R.id.tvTodayAvgNightTemp);
        mtvTodayMinTemp = view.findViewById(R.id.tvTodayMinTemp);
        mtvTodayMaxTemp = view.findViewById(R.id.tvTodayMaxTemp);
        mtvTodayMinHumid = view.findViewById(R.id.tvTodayMinHumid);
        mtvTodayMaxHumid = view.findViewById(R.id.tvTodayMaxHumid);

        mtvYesterdayDate = view.findViewById(R.id.tvYesterdayDate);
        mtvYesterdayAvgDayTemp = view.findViewById(R.id.tvYesterdayAvgDayTemp);
        mtvYesterdayAvgNightTemp = view.findViewById(R.id.tvYesterdayAvgNightTemp);
        mtvYesterdayMinTemp = view.findViewById(R.id.tvYesterdayMinTemp);
        mtvYesterdayMaxTemp = view.findViewById(R.id.tvYesterdayMaxTemp);
        mtvYesterdayMinHumid = view.findViewById(R.id.tvYesterdayMinHumid);
        mtvYesterdayMaxHumid = view.findViewById(R.id.tvYesterdayMaxHumid);

        mtvWeekDate = view.findViewById(R.id.tvWeekDate);
        mtvWeekAvgDayTemp = view.findViewById(R.id.tvWeekAvgDayTemp);
        mtvWeekAvgNightTemp = view.findViewById(R.id.tvWeekAvgNightTemp);
        mtvWeekMinTemp = view.findViewById(R.id.tvWeekMinTemp);
        mtvWeekMaxTemp = view.findViewById(R.id.tvWeekMaxTemp);
        mtvWeekMinHumid = view.findViewById(R.id.tvWeekMinHumid);
        mtvWeekMaxHumid = view.findViewById(R.id.tvWeekMaxHumid);
    }

    void loadGreenhouseData(){
        mSwipeRefreshLayout.setRefreshing(true);
        dialog = new ProgressDialog(getContext());
        dialog.setMessage("Loading....");
        dialog.show();
        DGH dgh = (DGH) getContext();

        StringRequest request = new StringRequest(url, new Response.Listener<String>() {
            @Override
            public void onResponse(String string) {

                parseJsonData(string, dgh.getCurrentGreenhouse());
            }
        }, new Response.ErrorListener() {
            @Override
            public void onErrorResponse(VolleyError volleyError) {
                Snackbar.make(getActivity().findViewById(android.R.id.content), volleyError.toString(), Snackbar.LENGTH_SHORT).show();
                dialog.dismiss();
                mSwipeRefreshLayout.setRefreshing(false);
            }
        });

        RequestQueue rQueue = Volley.newRequestQueue(getContext());
        rQueue.add(request);
    }

    void parseJsonData(String jsonString, String currGH) {
        mSwipeRefreshLayout.setRefreshing(false);
        dialog.dismiss();

        try {
            JSONObject jsonObject = new JSONObject(jsonString);
            if(jsonObject.getString("status").equalsIgnoreCase("OK")) {
                SimpleDateFormat df = new SimpleDateFormat("dd/MM/yyyy");
                Calendar calToday = Calendar.getInstance();
                Calendar calYesterday = Calendar.getInstance();
                Calendar calWeek = Calendar.getInstance();
                calYesterday.add(Calendar.DATE, -1);
                calWeek.add(Calendar.DATE, -7);

                String today = df.format(calToday.getTime());
                String yesterday = df.format(calYesterday.getTime());
                String week = df.format(calWeek.getTime());

                mtvGreenhouse.setText("Greenhouse " + currGH);
                mtvTodayDate.setText(today);
                if(jsonObject.getString("today_avg_day").equalsIgnoreCase("Not Day Yet!")) {
                    mtvTodayAvgDayTemp.setText(jsonObject.getString("today_avg_day"));
                } else {
                    mtvTodayAvgDayTemp.setText(jsonObject.getString("today_avg_day") + "°C");
                }
                if(jsonObject.getString("today_avg_night").equalsIgnoreCase("Not Night Yet!")) {
                    mtvTodayAvgNightTemp.setText(jsonObject.getString("today_avg_night"));
                } else {
                    mtvTodayAvgNightTemp.setText(jsonObject.getString("today_avg_night") + "°C");
                }
                mtvTodayMinTemp.setText(jsonObject.getString("today_min_temp") + "°C");
                mtvTodayMaxTemp.setText(jsonObject.getString("today_max_temp") + "°C");
                mtvTodayMinHumid.setText(jsonObject.getString("today_min_humid") + "%");
                mtvTodayMaxHumid.setText(jsonObject.getString("today_max_humid") + "%");

                mtvYesterdayDate.setText(yesterday);
                mtvYesterdayAvgDayTemp.setText(jsonObject.getString("yesterday_avg_day") + "°C");
                mtvYesterdayAvgNightTemp.setText(jsonObject.getString("yesterday_avg_night") + "°C");
                mtvYesterdayMinTemp.setText(jsonObject.getString("yesterday_min_temp") + "°C");
                mtvYesterdayMaxTemp.setText(jsonObject.getString("yesterday_max_temp") + "°C");
                mtvYesterdayMinHumid.setText(jsonObject.getString("yesterday_min_humid") + "%");
                mtvYesterdayMaxHumid.setText(jsonObject.getString("yesterday_max_humid") + "%");

                mtvWeekDate.setText(week + " - " + yesterday);
                mtvWeekAvgDayTemp.setText(jsonObject.getString("week_avg_day") + "°C");
                mtvWeekAvgNightTemp.setText(jsonObject.getString("week_avg_night") + "°C");
                mtvWeekMinTemp.setText(jsonObject.getString("week_min_temp") + "°C");
                mtvWeekMaxTemp.setText(jsonObject.getString("week_max_temp") + "°C");
                mtvWeekMinHumid.setText(jsonObject.getString("week_min_humid") + "%");
                mtvWeekMaxHumid.setText(jsonObject.getString("week_max_humid") + "%");
                mSwipeRefreshLayout.setRefreshing(false);
                dialog.dismiss();

            } else {
                Snackbar.make(getActivity().findViewById(android.R.id.content), jsonObject.getString("message"), Snackbar.LENGTH_SHORT).show();
            }
        } catch (JSONException e) {
            e.printStackTrace();
        }

    }
}



