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

import java.util.ArrayList;
import java.util.List;

public class fragViewGreenhouse extends Fragment implements SwipeRefreshLayout.OnRefreshListener {
    private boolean itemSelected = false;
    private SwipeRefreshLayout mSwipeRefreshLayout;

    private RecyclerView mrvGreenhouses;
    private GreenhouseAdapter greenhouseAdapter;
    private String url = "http://***REMOVED***/api.php?c=latest&a=***REMOVED***";
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
        View rootView = inflater.inflate(R.layout.fragment_home, container, false);


        mrvGreenhouses = (RecyclerView) rootView.findViewById(R.id.rvGreenhouses);
        mrvGreenhouses.setHasFixedSize(true);//every item of the RecyclerView has a fix size
        mrvGreenhouses.setLayoutManager(new LinearLayoutManager(getContext()));

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
        mrvGreenhouses = view.findViewById(R.id.rvGreenhouses);
        //DividerItemDecoration dividerItemDecoration = new DividerItemDecoration(mrvGreenhouses.getContext(), LinearLayoutManager.VERTICAL);
        //mrvGreenhouses.addItemDecoration(dividerItemDecoration);

        //loadGreenhouseData();

        mrvGreenhouses.addOnItemTouchListener(new RecyclerTouchListener(getContext(), mrvGreenhouses, new RecyclerTouchListener.ClickListener() {
            @Override
            public void onClick(View view, int position) {
                String selectedItem = ((TextView) mrvGreenhouses.findViewHolderForAdapterPosition(position).itemView.findViewById(R.id.tvName)).getText().toString();
                selectedItem = selectedItem.replace("Greenhouse ", "");
                Snackbar.make(getActivity().findViewById(android.R.id.content), selectedItem, Snackbar.LENGTH_SHORT).show();
                DGH dgh = (DGH) getContext();

                dgh.setCurrentGreenhouse(selectedItem);
                dgh.replaceFragment(new fragViewGreenhouse(), false);
            }
        }));
    }

    void loadGreenhouseData(){
        mSwipeRefreshLayout.setRefreshing(true);
        dialog = new ProgressDialog(getContext());
        dialog.setMessage("Loading....");
        dialog.show();

        StringRequest request = new StringRequest(url, new Response.Listener<String>() {
            @Override
            public void onResponse(String string) {
                dialog.dismiss();
                parseJsonData(string);
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

    void parseJsonData(String jsonString) {
        try {
            JSONObject jsonObject = new JSONObject(jsonString);
            if(jsonObject.getString("status") == "OK") {
                JSONArray greenhouseArray = jsonObject.getJSONArray("greenhouses");

                List<GHReading> greenhouses = new ArrayList<>();
                ArrayList al = new ArrayList();

                for (int i = 0; i < greenhouseArray.length(); ++i) {
                    JSONObject obj = greenhouseArray.getJSONObject(i);
                    greenhouses.add(new GHReading(obj.getString("sensor_id"), obj.getString("temperature"), obj.getString("humidity"), obj.getString("taken")));
                }

                greenhouseAdapter = new GreenhouseAdapter(greenhouses, getContext());
                mrvGreenhouses.setLayoutManager(new LinearLayoutManager(getContext(), LinearLayoutManager.VERTICAL, false));
                mrvGreenhouses.setAdapter(greenhouseAdapter);
            } else {
                Snackbar.make(getActivity().findViewById(android.R.id.content), jsonObject.getString("message"), Snackbar.LENGTH_SHORT).show();
            }
        } catch (JSONException e) {
            e.printStackTrace();
        }
        mSwipeRefreshLayout.setRefreshing(false);
        dialog.dismiss();
    }
}



