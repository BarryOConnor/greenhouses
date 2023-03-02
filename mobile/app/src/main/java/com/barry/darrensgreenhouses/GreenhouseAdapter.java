package com.barry.darrensgreenhouses;

import android.content.Context;
import android.util.Log;
import android.util.Pair;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageButton;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.recyclerview.widget.RecyclerView;

import java.text.ParseException;
import java.util.List;

public class GreenhouseAdapter extends RecyclerView.Adapter<GreenhouseAdapter.Greenhouse>{
    private List<GHReading> greenhouseList;
    Context context;

    public GreenhouseAdapter(List<GHReading> greenhouseList, Context context){
        this.greenhouseList = greenhouseList;
        this.context = context;
    }

    @Override
    public Greenhouse onCreateViewHolder(ViewGroup parent, int viewType) {
        //inflate the layout file
        View greenhouseView = LayoutInflater.from(parent.getContext()).inflate(R.layout.greenhouse, parent, false);
        Greenhouse outlet = new Greenhouse(greenhouseView);
        return outlet;
    }

    @Override
    public void onBindViewHolder(Greenhouse holder, final int position) {
        GHReading currentGreenhouse = greenhouseList.get(position);
        holder.mtvGreenhouseName.setText("Greenhouse " + currentGreenhouse.getId());
        holder.mtvTemperature.setText(currentGreenhouse.getTemperature() + "°C");
        holder.mtvHumidity.setText(currentGreenhouse.getHumidity() + "%");
        try {
            holder.mtvTaken.setText(currentGreenhouse.getTakenFormatted());
        } catch (ParseException e) {
            e.printStackTrace();
        }
    }

    @Override
    public int getItemCount() {
        return greenhouseList.size();
    }

    public class Greenhouse extends RecyclerView.ViewHolder {
        //ImageView mivGreenhouse;
        TextView mtvGreenhouseName;
        TextView mtvTemperature;
        TextView mtvHumidity;
        TextView mtvTaken;
        public Greenhouse(View view) {
            super(view);
            //mivGreenhouse = view.findViewById(R.id.ivGreenhouse);
            mtvGreenhouseName = view.findViewById(R.id.tvName);
            mtvTemperature = view.findViewById(R.id.tvTemperature);
            mtvHumidity = view.findViewById(R.id.tvHumidity);
            mtvTaken = view.findViewById(R.id.tvTaken);
        }
    }
}
