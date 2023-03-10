package com.barry.darrensgreenhouses;

import androidx.appcompat.app.AppCompatActivity;
import androidx.fragment.app.Fragment;
import androidx.fragment.app.FragmentTransaction;

import android.os.Bundle;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Button;
import android.widget.ProgressBar;
import android.widget.RelativeLayout;

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
            fragHome homeFragment = fragHome.newInstance();
            getSupportFragmentManager()
                    .beginTransaction()
                    .replace(R.id.mainFrame, homeFragment)
                    .commit();
        }

    }

    @Override
    protected void onStart() {
        super.onStart();

    }

    public void replaceFragment(Fragment fragment, boolean remove) {
        String[] result = fragment.toString().split("[{]");
        if(remove){
            getSupportFragmentManager().popBackStack();
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