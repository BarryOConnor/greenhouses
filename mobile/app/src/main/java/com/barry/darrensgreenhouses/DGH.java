package com.barry.darrensgreenhouses;

import androidx.appcompat.app.AppCompatActivity;
import androidx.fragment.app.Fragment;

import android.os.Bundle;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Button;
import android.widget.ProgressBar;

import com.google.android.material.snackbar.Snackbar;

import java.util.ArrayList;
import java.util.List;

public class DGH extends AppCompatActivity {
    private Button mbtnHome;
    private String mCurrentGreenhouse;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        mbtnHome = findViewById(R.id.btnHome);
        mbtnHome.setOnClickListener(new View.OnClickListener() {
            //OnClick event listener for the Login Button
            @Override
            public void onClick(View view) {
                replaceFragment(new fragHome(),true);
            }
        });

        if(savedInstanceState == null) {
            getSupportFragmentManager()
                    .beginTransaction()
                    .replace(R.id.mainFrame, fragHome.newInstance())
                    .commit();
        }
    }

    public void replaceFragment(Fragment fragment, boolean remove) {
        String[] result = fragment.toString().split("[{]");
        if(remove){
            getSupportFragmentManager().popBackStack(result[0],0);
        } else {
            getSupportFragmentManager().beginTransaction()
                    .replace(R.id.mainFrame, fragment, fragment.toString())
                    .addToBackStack(result[0])
                    .commit();
        }
    }


    public String getCurrentGreenhouse(){
        return mCurrentGreenhouse;
    }

    public void setCurrentGreenhouse(String currentGreenhouse){
        mCurrentGreenhouse = currentGreenhouse;
    }
}